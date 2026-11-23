import {useEffect,useRef,useState} from 'react';
import {api,type Programme,type ReviewTemplate} from './operationalApi';

type TemplateData={templates:ReviewTemplate[];programmes:Programme[]};
type Criterion={name:string;max:string};
const criteriaOf=(template:ReviewTemplate):{name:string;max:number}[]=>typeof template.criteria==='string'?JSON.parse(template.criteria):template.criteria;

export default function ConnectedTemplates(){
 const [data,setData]=useState<TemplateData|null>(null),[error,setError]=useState(''),[notice,setNotice]=useState('');
 const [kind,setKind]=useState('all'),[editing,setEditing]=useState<ReviewTemplate|'new'|null>(null);
 const editorDialog=useRef<HTMLDialogElement>(null);
 useEffect(()=>{if(editing)editorDialog.current?.showModal();else editorDialog.current?.close()},[editing]);
 const dialog=useRef<HTMLDialogElement>(null);
 const [pendingDelete,setPendingDelete]=useState<ReviewTemplate|null>(null);
 const [name,setName]=useState(''),[programme,setProgramme]=useState(''),[formKind,setFormKind]=useState('evaluation'),[criteria,setCriteria]=useState<Criterion[]>([{name:'',max:''}]),[busy,setBusy]=useState(false);
 async function load(){try{setData(await api<TemplateData>('review-templates'));setError('')}catch(e){setError((e as Error).message)}}
 useEffect(()=>{void load()},[]);
 if(!data)return <p role={error?'alert':'status'}>{error||'Loading criteria templates…'}{error&&<button className="btn" onClick={()=>void load()}>Retry</button>}</p>;
 const visible=data.templates.filter(t=>kind==='all'||t.kind===kind);
 const programmeName=(id:number)=>data.programmes.find(p=>p.id===id)?.name||'Programme';
 function open(template:ReviewTemplate|'new'){setEditing(template);setName(template==='new'?'':template.name);setProgramme(String(template==='new'?data?.programmes[0]?.id||'':template.programme_id));setFormKind(template==='new'?'evaluation':template.kind);setCriteria(template==='new'?[{name:'',max:''}]:criteriaOf(template).map(c=>({name:c.name,max:String(c.max)})));setError('');setNotice('')}
 function changeCriterion(index:number,key:keyof Criterion,value:string){setCriteria(rows=>rows.map((row,i)=>i===index?{...row,[key]:value}:row))}
 async function save(e:React.FormEvent){e.preventDefault();if(!editing)return;setError('');setNotice('');const clean=criteria.map(c=>({name:c.name.trim(),max:Number(c.max)}));if(clean.some(c=>!c.name||!Number.isInteger(c.max)||c.max<1)){setError('Give every criterion a name and a whole-number maximum score of at least 1.');return}if(new Set(clean.map(c=>c.name.toLowerCase())).size!==clean.length){setError('Each criterion needs a distinct name.');return}setBusy(true);try{const creating=editing==='new';await api(creating?'review-templates':`review-templates/${editing.id}`,creating?'POST':'PATCH',{programme_id:Number(programme),name:name.trim(),kind:formKind,criteria:clean});await load();setEditing(null);setNotice(creating?'Template created.':'Template updated.')}catch(err){setError((err as Error).message)}finally{setBusy(false)}}
 async function remove(template:ReviewTemplate){setBusy(true);setError('');try{await api(`review-templates/${template.id}`,'DELETE');await load();setNotice('Template deleted.')}catch(err){setError((err as Error).message)}finally{setBusy(false)}}
 return <div className="templates-page"><div className="toolbar"><h2>Criteria Templates</h2></div>
 {notice&&<p role="status" className="template-notice">{notice}</p>}{error&&!editing&&<p role="alert" className="report-error">{error}</p>}
 <div className="template-filters"><label>Review type<select value={kind} onChange={e=>setKind(e.target.value)}><option value="all">Both review types</option><option value="evaluation">Evaluator scoring</option><option value="adjudication">Adjudicator decision</option></select></label></div>
 <div className="template-list">
 <article className="template-create-card"><button className="template-create-button" onClick={()=>open('new')}><span aria-hidden="true">+</span><strong>Create template</strong></button></article>
 {visible.map(t=>{
  const rows=criteriaOf(t),total=rows.reduce((n,c)=>n+Number(c.max),0),locked=Number(t.use_count||0)>0;
  return <article className="template-item" key={t.id}>
   <div className="template-item-heading"><small>{programmeName(t.programme_id)} · {t.kind==='evaluation'?'Evaluator scoring':'Adjudicator decision'}</small><h3>{t.name}</h3></div>
   <div className="template-preview" tabIndex={0} role="region" aria-label={`Criteria for ${t.name}`}>
    <ol>{rows.map((c,i)=><li key={i}><span>{c.name}</span><strong>{c.max} pts</strong></li>)}</ol>
   </div>
   <div className="template-card-footer"><div className="template-total"><span>Total</span><strong>{total} pts</strong></div>
    <button className="btn" aria-label="Edit" title={locked?'Used in a review; cannot edit':'Edit'} disabled={locked||busy} onClick={()=>open(t)}><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m14 6 4 4M4 20l4-.8L20 7l-4-4L4.8 15z"/></svg></button>
    <button className="btn template-delete" aria-label="Delete" title={locked?'Used in a review; cannot delete':'Delete'} disabled={locked||busy} onClick={()=>{setPendingDelete(t);dialog.current?.showModal()}}><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg></button>
   </div>
  </article>
 })}

 </div>
 <dialog ref={dialog} className="template-confirm" aria-labelledby="template-delete-title" onClose={()=>setPendingDelete(null)}>
  <h3 id="template-delete-title">Delete template?</h3>
  <p>Are you sure you want to delete “{pendingDelete?.name}”? This cannot be undone.</p>
  <form method="dialog" className="actions"><button className="btn" autoFocus>Cancel</button><button className="btn primary" type="button" onClick={()=>{if(pendingDelete&&!busy){dialog.current?.close();void remove(pendingDelete)}}}>Delete template</button></form>
 </dialog>
 <dialog ref={editorDialog} className="template-editor template-editor-modal" aria-label="Template editor" onCancel={e=>{if(busy)e.preventDefault()}} onClose={()=>setEditing(null)}>{editing&&<><div className="toolbar"><h3>{editing==='new'?'Create template':'Edit template'}</h3><button className="btn" disabled={busy} onClick={()=>setEditing(null)}>Cancel</button></div>{error&&<p role="alert" className="report-error">{error}</p>}<form onSubmit={save}><div className="template-fields"><label>Programme<select required value={programme} onChange={e=>setProgramme(e.target.value)}>{data.programmes.map(p=><option key={p.id} value={p.id}>{p.name}</option>)}</select></label><label>Review type<select value={formKind} onChange={e=>setFormKind(e.target.value)}><option value="evaluation">Evaluator scoring</option><option value="adjudication">Adjudicator decision</option></select></label></div><label>Template name<input required maxLength={120} value={name} onChange={e=>setName(e.target.value)} placeholder="e.g. Business idea evaluation"/></label><div className="template-criteria-editor"><h4>Scoring criteria</h4>{criteria.map((c,i)=><div className="criterion-row" key={i}><label>Criterion {i+1}<input required maxLength={150} value={c.name} onChange={e=>changeCriterion(i,'name',e.target.value)} placeholder="e.g. Feasibility"/></label><label>Maximum points<input required type="number" min="1" max="1000" step="1" value={c.max} onChange={e=>changeCriterion(i,'max',e.target.value)} placeholder="10"/></label><button type="button" className="btn" disabled={criteria.length===1} onClick={()=>setCriteria(rows=>rows.filter((_,n)=>n!==i))} aria-label={`Remove criterion ${i+1}`}>Remove</button></div>)}<div className="template-editor-footer"><button type="button" className="btn" disabled={criteria.length>=30} onClick={()=>setCriteria(rows=>[...rows,{name:'',max:''}])}>Add criterion</button><strong>{criteria.reduce((n,c)=>n+(Number(c.max)||0),0)} points total</strong></div></div><button className="btn primary" disabled={busy}>{busy?'Saving…':editing==='new'?'Create template':'Save changes'}</button></form></>}</dialog>
 </div>;
}
