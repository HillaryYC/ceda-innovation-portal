import {useEffect,useState} from 'react';
import {api} from './operationalApi';
import type {Criterion,Mark,ReviewNote} from './MyEvaluations';
import type {ReviewerRequest} from './ReviewerInformation';

export type SubmittedEvaluation={id:number;evaluator:string;is_lead:boolean;marks:Mark[];overall_comment:string;review_notes:ReviewNote[];recommendation:string;submitted_at:string;template:string;criteria:Criterion[]};
export type Adjudication={id:number;idea_id:number;status:string;due_date:string|null;title:string;applicant:string;category:string;summary:string;problem:string;solution:string;form_answers?:Record<string,unknown>;form_schema?:Record<string,unknown>;attachments?:{id:number;name:string;size:number}[];template:string;criteria:Criterion[];marks:Mark[];review_notes:ReviewNote[];overall_comment:string|null;recommendation:string|null;submitted_at:string|null;evaluations:SubmittedEvaluation[];evaluator_count:number;information_requests?:ReviewerRequest[]};
export default function MyAdjudications({section}:{section:string}){
 const [items,setItems]=useState<Adjudication[]>([]),[loading,setLoading]=useState(true),[error,setError]=useState('');
 async function load(){setError('');try{setItems((await api<{data:Adjudication[]}>('my-adjudications')).data)}catch(e){setError((e as Error).message)}finally{setLoading(false)}}
 useEffect(()=>{void load()},[]);
 return <><div className="toolbar"><h2>My Adjudications</h2><button className="btn" onClick={()=>void load()}>Refresh</button></div>{error&&<p role="alert">{error}</p>}{loading?<p role="status">Loading assigned ideas…</p>:<div className="grid evaluator-applications">{items.filter(i=>i.status===section).map(i=><article className="card application-card" key={i.id}><div className="idea-heading"><small>IDEA-{i.idea_id}</small><span className="pill">{i.status==='in_progress'?'In progress':i.status==='submitted'?'Completed':'Pending'}</span></div><h3>{i.title}</h3><p>{i.summary}</p><div className="card-facts"><div><small>Submitted evaluations</small><span>{i.evaluations.length} of {i.evaluator_count}</span></div><div><small>Deadline</small><span>{i.due_date||'No deadline'}</span></div></div><div className="card-bottom"><a className="btn primary" href={`/adjudication/${i.id}`}>{i.status==='submitted'?'View decision':'Assess idea'} →</a></div></article>)}</div>}{!loading&&!error&&!items.some(i=>i.status===section)&&<p className="report-empty">No adjudications in this tab.</p>}</>;
}
