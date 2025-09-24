// Minimal React PWA using existing CSS, with offline queue and local JSON storage
(function(){
  const e = React.createElement;

  const API = {
    me: () => fetch('/simple_accounting/api/me.php', {credentials:'same-origin'}).then(r=>r.json()),
    accounts: () => fetch('/simple_accounting/api/accounts.php', {credentials:'same-origin'}).then(r=>r.json()),
    createAccount: (name) => fetch('/simple_accounting/api/accounts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({name})}).then(r=>r.json()),
    transactions: (account_id) => fetch('/simple_accounting/api/transactions.php?account_id=' + encodeURIComponent(account_id), {credentials:'same-origin'}).then(r=>r.json()),
    transact: (payload) => fetch('/simple_accounting/api/transactions.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify(payload)}).then(r=>r.json()),
    dump: () => fetch('/simple_accounting/api/dump.php', {credentials:'same-origin'}).then(r=>r.json()),
    batch: (actions) => fetch('/simple_accounting/api/batch.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({actions})}).then(r=>r.json()),
  };

  // Local offline storage helpers (JSON DB)
  const DB = {
    load: () => {
      try { return JSON.parse(localStorage.getItem('pwa_db')||'{"accounts":[],"transactions":[]}'); } catch(e){ return {accounts:[],transactions:[]}; }
    },
    save: (db) => localStorage.setItem('pwa_db', JSON.stringify(db)),
    queue: () => { try { return JSON.parse(localStorage.getItem('offlineQueue')||'[]'); } catch(e){ return []; } },
    saveQueue: (q) => localStorage.setItem('offlineQueue', JSON.stringify(q)),
  };

  function useOnlineStatus() {
    const [online, setOnline] = React.useState(navigator.onLine);
    React.useEffect(()=>{
      const on = ()=>setOnline(true), off=()=>setOnline(false);
      window.addEventListener('online', on); window.addEventListener('offline', off);
      return ()=>{ window.removeEventListener('online', on); window.removeEventListener('offline', off); };
    },[]);
    return online;
  }

  function Badge({children, type}){
    const cls = type==='pos'?'badge pos':type==='neg'?'badge neg':'badge zero';
    return e('span', {className: cls}, children);
  }

  function balanceClass(v){ return v>0?'text-pos':v<0?'text-neg':'text-zero'; }

  function App(){
    const [loading, setLoading] = React.useState(true);
    const [user, setUser] = React.useState(null);
    const [accounts, setAccounts] = React.useState([]);
    const [offlineMode, setOfflineMode] = React.useState(false);
    const online = useOnlineStatus();
    const [message, setMessage] = React.useState('');

    React.useEffect(()=>{
      // Load user and accounts from API or local
      (async ()=>{
        try {
          const me = await API.me();
          if (me.ok && me.user) setUser(me.user);
          if (online) {
            const dump = await API.dump();
            if (dump.ok) {
              DB.save(dump.data);
              setAccounts(await shapeAccounts(dump.data));
            }
          } else {
            const local = DB.load();
            setAccounts(await shapeAccounts(local));
          }
        } catch(err){
          const local = DB.load();
          setAccounts(await shapeAccounts(local));
        } finally {
          setLoading(false);
        }
      })();
    }, [online]);

    React.useEffect(()=>{
      // Try to flush offline queue when online
      if (!online) return;
      const q = DB.queue();
      if (!q.length) return;
      API.batch(q).then(res=>{
        if (res.ok) {
          DB.saveQueue([]);
          setMessage('Synced offline changes.');
          // Refresh
          API.dump().then(d=>{ if (d.ok){ DB.save(d.data); shapeAccounts(d.data).then(setAccounts); }});
        }
      }).catch(()=>{});
    }, [online]);

    async function shapeAccounts(data){
      // compute balances from transactions
      const map = new Map(data.accounts.map(a=>[a.id, {...a, balance:0}]));
      for (const t of data.transactions){
        const acc = map.get(t.account_id); if (!acc) continue;
        acc.balance += (t.type==='credit'? t.amount : -t.amount);
      }
      return Array.from(map.values()).sort((a,b)=> new Date(b.created_at)-new Date(a.created_at));
    }

    async function onCreateAccount(){
      const name = prompt('Account name'); if (!name) return;
      if (offlineMode || !online) {
        const db = DB.load();
        const id = (db.accounts.reduce((m,a)=>Math.max(m,a.id),0) || 0) + 1;
        db.accounts.push({id, name, created_at: new Date().toISOString().slice(0,19).replace('T',' ')});
        DB.save(db);
        const q = DB.queue(); q.push({type:'create_account', name}); DB.saveQueue(q);
        setAccounts(await shapeAccounts(db));
        setMessage('Account saved offline and queued.');
      } else {
        const res = await API.createAccount(name);
        if (res.ok) {
          const dump = await API.dump(); if (dump.ok){ DB.save(dump.data); setAccounts(await shapeAccounts(dump.data)); }
        }
      }
    }

    async function onTransact(accId, type){
      const amountStr = prompt(type+': amount'); if (!amountStr) return; const amount = parseFloat(amountStr);
      if (!(amount>0)) return;
      const note = prompt('Note (optional)') || '';
      if (offlineMode || !online) {
        const db = DB.load();
        const tid = (db.transactions.reduce((m,t)=>Math.max(m,t.id),0) || 0) + 1;
        db.transactions.push({id:tid, account_id:accId, type, amount, note, created_at: new Date().toISOString().slice(0,19).replace('T',' ')});
        DB.save(db);
        const q = DB.queue(); q.push({type:'transaction', account_id: accId, action: type, amount, note}); DB.saveQueue(q);
        setAccounts(await shapeAccounts(db));
        setMessage('Transaction saved offline and queued.');
      } else {
        const res = await API.transact({account_id:accId, action:type, amount, note});
        if (res.ok) {
          const dump = await API.dump(); if (dump.ok){ DB.save(dump.data); setAccounts(await shapeAccounts(dump.data)); }
        }
      }
    }

    if (loading) return e('div', null, 'Loading...');

    return e('div', null,
      e('div', {className:'card'},
        e('div', {className:'toolbar'},
          e('button', {className:'btn', onClick:onCreateAccount}, '+ New Account'),
          e('label', null,
            e('input', {type:'checkbox', checked: offlineMode, onChange:ev=>setOfflineMode(ev.target.checked)}),
            ' Offline mode'
          ),
          e('span', {className:'badge ' + (online?'pos':'neg')}, online?'Online':'Offline'),
          message ? e('span', {className:'badge info'}, message) : null
        )
      ),
      e('div', {className:'card'},
        e('div', {className:'table-wrap'},
          e('table', {className:'table', style:{minWidth:'720px'}},
            e('thead', null, e('tr', null,
              e('th', null, 'Name'),
              e('th', null, 'Status'),
              e('th', null, 'Balance'),
              e('th', null, 'Created'),
              e('th', null, 'Actions'),
            )),
            e('tbody', null,
              accounts.length? accounts.map(a=> e('tr', {key:a.id},
                e('td', null, a.name),
                e('td', null, a.balance>0? e(Badge,{type:'pos'},'To Receive'): a.balance<0? e(Badge,{type:'neg'},'To Return') : e(Badge,{type:'zero'},'Settled')),
                e('td', {className:'balance '+balanceClass(a.balance)}, a.balance.toFixed(2)),
                e('td', null, a.created_at.slice(0,10)),
                e('td', null,
                  e('button', {className:'btn', onClick:()=>onTransact(a.id,'credit')}, 'Credit'),
                  ' ',
                  e('button', {className:'btn secondary', onClick:()=>onTransact(a.id,'debit')}, 'Debit')
                )
              )) : e('tr', null, e('td', {colSpan:5}, 'No accounts'))
            )
          )
        )
      )
    );
  }

  ReactDOM.createRoot(document.getElementById('root')).render(e(App));
})();
