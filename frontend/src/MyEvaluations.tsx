import {useEffect,useState} from 'react';
import {api} from './operationalApi';
import type {ReviewerRequest} from './ReviewerInformation';
export type Criterion={name:string;max:number};
export type Mark={score:number|null;comment:string};
export type ReviewNote={criterion:number;section:string;kind:'Highlight'|'Comment'|'Flag'|'Evidence';quote:string;text:string};
export type Evaluation={form_schema?:import('./ApplicantForm').FormDefinition;form_answers?:import('./ApplicantForm').FormAnswers;attachments?:import('./ApplicantForm').Attachment[];id:number;idea_id:number;status:string;due_date:string|null;title:string;applicant:string;category:string;summary:string;problem:string;solution:string;template:string;criteria:string|Criterion[];marks:Mark[]|string|null;overall_comment:string|null;review_notes:ReviewNote[]|string|null;completed_sections:number[]|string|null;recommendation:string|null;information_requests?:ReviewerRequest[]};
export default function MyEvaluations({section}:{section:string}){
 const [items,setItems]=useState<Evaluation[]>([]),[error,setError]=useState(''),[loading,setLoading]=useState(true);
 async function load(){setError('');try{setItems((await api<{data:Evaluation[]}>('my-evaluations')).data)}catch(e){setError((e as Error).message)}finally{setLoading(false)}}
 useEffect(()=>{void load()},[]);
 return <><div className="toolbar"><h2>My Evaluations</h2><button className="btn" onClick={()=>void load()}>Refresh</button></div>{error&&<p role="alert">{error}</p>}{loading?<p role="status">Loading assigned applications…</p>:<div className="grid evaluator-applications">{items.filter(i=>i.status===section).map(i=><article className="card application-card" key={i.id}><div className="idea-heading"><small>IDEA-{i.idea_id}</small><span className="pill">{i.status==='pending'?'Pending':i.status==='submitted'?'Completed':'In progress'}</span></div><h3>{i.title}</h3><div className="applicant-line"><span>Applicant</span><strong>{i.applicant}</strong></div><p>{i.summary}</p><div className="card-facts"><div><small>Category</small><span>{i.category}</span></div><div><small>Deadline</small><span>{i.due_date?i.due_date.split('-').reverse().join('/'):'No deadline'}</span></div></div><div className="card-bottom"><a className="btn primary" href={`/evaluation/${i.id}`}>{i.status==='submitted'?'View evaluation':'Open application'} →</a></div></article>)}</div>}{!loading&&!error&&!items.some(i=>i.status===section)&&<p className="report-empty">No evaluations in this tab.</p>}</>;
}

