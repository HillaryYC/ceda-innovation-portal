import type {ReactNode} from 'react';
import {CaretDown} from '@phosphor-icons/react';
import type {FormDefinition,FormAnswers,Attachment} from './ApplicantForm';

function Section({title,children}:{title:string;children:ReactNode}){
 return <details className="applicant-submission-section"><summary><span>{title}</span><CaretDown className="applicant-section-chevron" size={19} weight="bold" aria-hidden="true"/></summary><div className="applicant-section-body">{children}</div></details>;
}

export default function SubmissionExtras({application}:{application:{category:string;form_schema?:FormDefinition;form_answers?:FormAnswers;attachments?:Attachment[]}}){
 const {form_schema:schema,form_answers:answers}=application;
 const files=(items:Attachment[])=><ul className="idea-attachment-list">{items.map(f=><li key={f.id}><a href={`/api/v1/attachments/${f.id}`}>{f.name}</a><small>{Math.ceil(f.size/1024)} KB</small></li>)}</ul>;
 if(schema?.mode==='document'){
  const proposal=application.attachments?.filter(f=>f.kind==='proposal')||[];
  const supporting=application.attachments?.filter(f=>f.kind!=='proposal')||[];
  return <><Section title="Business proposal">{proposal.length?files(proposal):<p>No proposal attached.</p>}</Section>{!!supporting.length&&<Section title="Supporting documents">{files(supporting)}</Section>}</>;
 }
 if(schema?.layout==='smart-v1'&&answers){
  const smart=answers as typeof answers & {core_answers?:Record<string,string|string[]>;category_answers?:Record<string,Record<string,string|string[]>>};
  const value=(item:string|string[]|undefined)=>Array.isArray(item)?item.join(', '):item||'Not provided';
  const fields=(questions:NonNullable<typeof schema.core>[string],source:Record<string,string|string[]>|undefined)=>questions.map(q=><div key={q.key}><h4>{q.label}</h4><p>{value(source?.[q.key])}</p></div>);
  const selected=schema.categories[application.category]?.questions.filter(q=>!q.stages?.length||q.stages.includes(answers.business_stage||''))||[];
  return <><Section title="Applicant and team"><h4>Business stage</h4><p>{answers.business_stage||'Not provided'}</p>{fields(schema.core?.about||[],smart.core_answers)}</Section><Section title="Idea and market"><h4>Target customers</h4><p>{answers.customers||'Not provided'}</p>{fields(schema.core?.idea||[],smart.core_answers)}</Section><Section title="Business evidence">{fields(selected,smart.category_answers?.[application.category])}</Section><Section title="Funding plan">{fields(schema.core?.funding||[],smart.core_answers)}</Section></>;
 }
 return <>{answers&&<Section title="Application details"><h4>Target customers</h4><p>{answers.customers||'Not provided'}</p><h4>Business stage</h4><p>{answers.business_stage||'Not provided'}</p>{schema?.categories[application.category]?.questions.filter(q=>!q.stages?.length||q.stages.includes(answers.business_stage||'')).map(q=><div key={q.key}><h4>{q.label}</h4><p>{q.type==='file'?(application.attachments?.find(f=>String(f.id)===answers.category_answers?.[application.category]?.[q.key])?.name||'Not provided'):(answers.category_answers?.[application.category]?.[q.key]||'Not provided')}</p></div>)}</Section>}{!!application.attachments?.length&&<Section title="Attachments">{files(application.attachments)}</Section>}</>;
}
