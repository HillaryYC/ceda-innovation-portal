import {Bell} from '@phosphor-icons/react';
import {useEffect,useState} from 'react';
import {api} from './operationalApi';

type Item={id:number;idea_id:number;kind:string;title:string;message:string;read_at:string|null;created_at:string};
type Result={unread:number;data:Item[]};
export default function NotificationBell({onOpen}:{onOpen:(item:Item)=>void}){
 const [result,setResult]=useState<Result>({unread:0,data:[]}),[open,setOpen]=useState(false),[error,setError]=useState('');
 useEffect(()=>{let active=true;const load=()=>{if(document.visibilityState==='hidden')return;api<Result>('notifications').then(r=>{if(active)setResult(r)}).catch(()=>{if(active)setError('Notifications are unavailable.')})};load();const timer=window.setInterval(load,30000);window.addEventListener('focus',load);return()=>{active=false;window.clearInterval(timer);window.removeEventListener('focus',load)}},[]);
 async function select(item:Item){try{if(!item.read_at){await api(`notifications/${item.id}/read`,'POST');setResult(v=>({...v,unread:Math.max(0,v.unread-1),data:v.data.map(n=>n.id===item.id?{...n,read_at:new Date().toISOString()}:n)}))}setOpen(false);onOpen(item)}catch(e){setError((e as Error).message)}}
 return <div className="notification-bell"><button className="notification-bell-button" aria-label={`Notifications${result.unread?`, ${result.unread} unread`:''}`} aria-expanded={open} aria-controls="notification-list" onClick={()=>setOpen(v=>!v)}><Bell size={22}/>{result.unread>0&&<span className="notification-count">{result.unread>9?'9+':result.unread}</span>}</button>{open&&<section id="notification-list" className="notification-panel" aria-label="Notifications"><div className="notification-panel-heading"><h2>Notifications</h2><button className="subtle" onClick={()=>setOpen(false)} aria-label="Close notifications">×</button></div>{error&&<p role="alert">{error}</p>}{result.data.length?<ul>{result.data.map(item=><li key={item.id}><button className={item.read_at?'':'unread'} onClick={()=>void select(item)}><strong>{item.title}</strong><span>{item.message}</span></button></li>)}</ul>:<p>No notifications yet.</p>}</section>}</div>;
}
