import {useEffect,useRef,useState} from 'react';
import OnlineApplicantForm from './OnlineApplicantForm';
import SmartApplicantForm from './SmartApplicantForm';
import {api} from './operationalApi';
import type {ApplicantApplication} from './ApplicantSubmissionCard';
export type Attachment={id:number;name:string;size:number;kind?:'proposal'|'supporting';category?:string;template_version?:string};
export type FormQuestion={key:string;label:string;hint:string;required:boolean;max:number;stages?:string[];group?:string;type?:'text'|'textarea'|'number'|'select'|'multiselect'|'file';options?:string[]};
export type FormDefinition={version:string;mode?:string;layout?:string;stages:string[];core?:Record<string,FormQuestion[]>;categories:Record<string,{description:string;family?:string;template_slug?:string;questions:FormQuestion[]}>};
export type FormAnswers={contact_phone?:string;customers?:string;business_stage?:string;category_answers?:Record<string,Record<string,string>>};
const steps=['Idea details','Business proposal','Review & submit'];
type Values={programme_id:number;title:string;category:string;location:string;summary:string;problem:string;solution:string;form_answers:FormAnswers;draft_step:number};
function DocumentApplicantForm({application,definition,definitions,programmes,account,onExit,onSubmitted,onProgress}:{application:Partial<ApplicantApplication>;definition:FormDefinition;definitions?:Record<number,FormDefinition>;programmes:{id:number;name:string}[];account:{name:string;email:string}|null;onExit:()=>void;onSubmitted:()=>void;onProgress?:(draft:Partial<ApplicantApplication>)=>void}){
 const schema=application.form_schema?.mode==='document'?application.form_schema:definition;
 const initialStep=application.form_schema?.mode==='document'?Math.min(application.draft_step||0,2):0;
 const [values,setValues]=useState<Values>(()=>({programme_id:application.programme_id||programmes[0]?.id||0,title:application.title||'',category:application.category||Object.keys(schema.categories)[0],location:application.location||'',summary:application.summary||'',problem:application.problem||'',solution:application.solution||'',form_answers:application.form_answers||{},draft_step:initialStep}));
 const [step,setStep]=useState(initialStep),[error,setError]=useState(''),[errors,setErrors]=useState<Record<string,string>>({}),[status,setStatus]=useState(application.id?'Draft saved':'Not saved yet'),[busy,setBusy]=useState(false),[submitting,setSubmitting]=useState(false),[uploading,setUploading]=useState(false),[categoryChanged,setCategoryChanged]=useState(false),[declaration,setDeclaration]=useState(false),[files,setFiles]=useState<Attachment[]>(application.attachments||[]);
 const latest=useRef(values),saved=useRef(application.saved_fingerprint??JSON.stringify(values)),id=useRef(application.id),inFlight=useRef<Promise<ApplicantApplication>|null>(null),heading=useRef<HTMLHeadingElement>(null),mounted=useRef(true);
 latest.current=values;
 useEffect(()=>{onProgress?.({...values,id:id.current,form_schema:schema,attachments:files,saved_fingerprint:saved.current})},[values,files,status]);
 useEffect(()=>{mounted.current=true;return()=>{mounted.current=false}},[]);
 useEffect(()=>{heading.current?.focus()},[step]);
 async function persist(submit=false):Promise<ApplicantApplication>{
  if(inFlight.current)await inFlight.current;
  const snapshot=latest.current,serial=JSON.stringify(snapshot);
  if(!submit&&id.current&&serial===saved.current&&application.form_schema?.mode==='document')return {...application,...snapshot,id:id.current} as ApplicantApplication;
  setBusy(true);setError('');setStatus(submit?'Submitting…':'Saving…');
  const request=api<{data:ApplicantApplication}>(id.current?`applicant/applications/${id.current}`:'applicant/applications',id.current?'PUT':'POST',{...snapshot,form_version:schema.version,submit,declaration:submit&&declaration}).then(r=>{id.current=r.data.id;saved.current=serial;onProgress?.({...latest.current,id:r.data.id,form_schema:schema,attachments:files,saved_fingerprint:serial});if(mounted.current)setStatus(submit?'Submitted':'Draft saved');return r.data});
  inFlight.current=request;
  try{return await request}catch(e){if(mounted.current){setError((e as Error).message);setStatus('Not saved — try again')}throw e}finally{inFlight.current=null;if(mounted.current)setBusy(false)}
 }
 useEffect(()=>{
  if(JSON.stringify(values)===saved.current)return;
  setStatus('Unsaved changes');
  const timer=setTimeout(()=>{void persist().catch(()=>{})},1500);
  return()=>clearTimeout(timer);
 },[values]);
 useEffect(()=>{const prevent=(event:BeforeUnloadEvent)=>{if(JSON.stringify(latest.current)!==saved.current||inFlight.current){event.preventDefault();event.returnValue=''}};window.addEventListener('beforeunload',prevent);return()=>window.removeEventListener('beforeunload',prevent)},[]);
 function update(key:keyof Values,value:string|number){setValues(v=>({...v,[key]:value}));setErrors({})}
 const proposal=files.find(f=>f.kind==='proposal'&&f.category===values.category&&f.template_version===schema.version);
 const oldProposal=files.find(f=>f.kind==='proposal');
 const supporting=files.filter(f=>f.kind!=='proposal');
 function validate(index:number){
  const e:Record<string,string>={};const required=(key:string,value:unknown,label:string)=>{if(!String(value||'').trim())e[key]=`${label} is required.`};
  if(index===0){required('location',values.location,'Location');required('programme_id',values.programme_id,'Programme');required('category',values.category,'Category');required('title',values.title,'Idea title');required('summary',values.summary,'Short summary')}
  if(index===1&&!proposal)e.proposal='Upload your completed Word or PDF proposal for '+values.category+'.';return e;
 }
 function go(next:number){if(next>step){const e=validate(step);if(Object.keys(e).length){setErrors(e);heading.current?.focus();return}}setErrors({});setError('');setStep(next);setValues(v=>({...v,draft_step:next}))}
 async function submit(){
  for(let index=0;index<2;index++){const e=validate(index);if(Object.keys(e).length){setStep(index);setErrors(e);return}}
  if(!declaration){setErrors({declaration:'Confirm the declaration before submitting.'});return}
  setSubmitting(true);try{await persist(true);onSubmitted()}catch{}finally{setSubmitting(false)}
 }
 async function upload(file:File,kind:'proposal'|'supporting'){
  const ext=file.name.split('.').pop()?.toLowerCase();
  if(!file.size||file.size>5*1024*1024||!(kind==='proposal'?['pdf','docx']:['pdf','docx','jpg','jpeg','png']).includes(ext||'')){setError(kind==='proposal'?'Choose a Word (.docx) or PDF proposal of up to 5 MB.':'Choose a PDF, Word (.docx), JPG or PNG file of up to 5 MB.');return}
  if((kind==='supporting'&&supporting.length>=3)||files.reduce((n,f)=>n+f.size,0)-(kind==='proposal'?(oldProposal?.size||0):0)+file.size>20*1024*1024){setError('Attach one proposal and up to 3 supporting files, with a combined size of 20 MB.');return}
  setUploading(true);setError('');
  try{const draft=await persist();const content=await new Promise<string>((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result).split(',')[1]);reader.onerror=()=>reject(new Error('The file could not be read.'));reader.readAsDataURL(file)});const r=await api<{data:Attachment}>(`applicant/applications/${draft.id}/attachments`,'POST',{name:file.name,content,kind,...(kind==='proposal'?{category:values.category,template_version:schema.version}:{})});setFiles(f=>[...f.filter(existing=>kind!=='proposal'||existing.kind!=='proposal'),r.data]);setErrors({})}catch(e){setError((e as Error).message)}finally{setUploading(false)}
 }
 async function remove(file:Attachment){setUploading(true);try{await api(`applicant/applications/${id.current}/attachments/${file.id}`,'DELETE');setFiles(f=>f.filter(i=>i.id!==file.id))}catch(e){setError((e as Error).message)}finally{setUploading(false)}}
 const fileList=(items:Attachment[])=><ul className="idea-attachment-list">{items.map(file=><li key={file.id}><div><a href={`/api/v1/attachments/${file.id}`}>{file.name}</a><small>{Math.ceil(file.size/1024)} KB</small></div><button type="button" className="subtle" aria-label={`Remove ${file.name}`} onClick={()=>void remove(file)}>Remove</button></li>)}</ul>;
 const fieldError=(key:string)=>errors[key]?<span className="idea-field-error" id={`error-${key}`}>{errors[key]}</span>:null;
 const fieldProps=(key:string)=>({'aria-invalid':!!errors[key],'aria-describedby':errors[key]?`error-${key}`:undefined});
 const reviewSection=(index:number,title:string,content:React.ReactNode)=><section className="idea-review-section"><div><h3>{title}</h3><button type="button" className="subtle" onClick={()=>go(index)} aria-label={`Edit ${title}`}>Edit</button></div>{content}</section>;
 return <div className="idea-wizard proposal-wizard">
  <div className="idea-wizard-toolbar"><span role="status" aria-live="polite" className="idea-save-status">{status}</span><button type="button" className="btn" disabled={busy||uploading} onClick={async()=>{try{await persist();onExit()}catch{}}}>Save & exit</button></div>
  <ol className={`idea-steps progress-${step}`} aria-label="Submission steps">{steps.map((label,index)=><li key={label}><button type="button" aria-label={`${index+1}. ${label}`} aria-current={index===step?'step':undefined} disabled={index>step} onClick={()=>go(index)}><span>{index+1}</span><span>{label}</span></button></li>)}</ol>
  <p className="idea-step-count">Step {step+1} of {steps.length}</p><h2 ref={heading} tabIndex={-1}>{steps[step]}</h2>
  {error&&<p role="alert" className="report-error">{error}<button type="button" className="subtle" disabled={busy} onClick={()=>void persist().catch(()=>{})}>Retry save</button></p>}
  {!!Object.keys(errors).length&&<p role="alert" className="idea-error-summary">Please complete the highlighted fields.</p>}
  <form noValidate onSubmit={e=>{e.preventDefault();if(step===2)void submit();else go(step+1)}}>
  <fieldset disabled={submitting||uploading} className="idea-step-fields">
  {step===0&&<><p>Enter the essentials here. You’ll explain the business in a proposal document in the next step.</p>
   <label htmlFor="idea-title">Idea title</label><input id="idea-title" maxLength={200} value={values.title} onChange={e=>update('title',e.target.value)} {...fieldProps('title')}/>{fieldError('title')}
   <label htmlFor="idea-programme">Programme</label><select id="idea-programme" value={values.programme_id} onChange={e=>update('programme_id',Number(e.target.value))} {...fieldProps('programme_id')}><option value={0}>Choose programme</option>{programmes.map(p=><option key={p.id} value={p.id}>{p.name}</option>)}</select>{fieldError('programme_id')}
   <label htmlFor="idea-category">Category</label><select id="idea-category" value={values.category} onChange={e=>update('category',e.target.value)}>{Object.keys(schema.categories).map(c=><option key={c}>{c}</option>)}</select><p className="idea-category-description">{schema.categories[values.category].description}</p>
   {oldProposal&&!proposal&&<p role="status" className="idea-category-notice">Your details are kept. Upload a completed proposal for {values.category} in the next step. Your previous proposal stays saved until you replace it.</p>}
   <label htmlFor="idea-location">Location</label><input id="idea-location" autoComplete="address-level2" maxLength={150} value={values.location} onChange={e=>update('location',e.target.value)} {...fieldProps('location')}/><small>Your town or village.</small>{fieldError('location')}
   <label htmlFor="idea-summary">Short summary</label><textarea id="idea-summary" rows={3} maxLength={3000} value={values.summary} onChange={e=>update('summary',e.target.value)} {...fieldProps('summary')}/><small>Briefly describe what your business will do. Put the full details in your proposal.</small>{fieldError('summary')}
  </>}
  {step===1&&<><p>Download the template, complete it in your own time, then upload your finished proposal. You can save and leave while you prepare it.</p>
   <section className="proposal-template"><span className="proposal-label">WORD TEMPLATE</span><h3>Your {values.category} proposal</h3><p>The template covers your business, customers, suppliers, operations, finances and category-specific details.</p><a className="btn" href={`/api/v1/applicant/templates/${schema.categories[values.category].template_slug}?version=${encodeURIComponent(schema.version)}`}>Download Word template</a></section>
   <h3 className="proposal-upload-heading">Upload completed proposal</h3><p className="proposal-upload-help">Required · Word (.docx) or PDF · up to 5 MB</p>
   {oldProposal&&fileList([oldProposal])}{oldProposal&&!proposal&&<p role="alert" className="idea-field-error">This proposal belongs to {oldProposal.category}. Replace it with a completed proposal for {values.category}.</p>}
   <label className="idea-upload" htmlFor="proposal-file"><strong>{oldProposal?'Replace proposal':'Choose your completed proposal'}</strong><span>Upload the document you completed using the template.</span><input id="proposal-file" type="file" accept=".pdf,.docx" onChange={e=>{const file=e.target.files?.[0];e.target.value='';if(file)void upload(file,'proposal')}}/></label>{fieldError('proposal')}
   <p className="proposal-check-note">We check that your file can be accepted. Reviewers will read the document to assess your idea.</p>
   <details className="proposal-supporting"><summary>Supporting documents <span>Optional</span></summary><p>Add any evidence, images or other supporting information. Up to 3 additional files.</p>{fileList(supporting)}<label className="idea-upload" htmlFor="support-file"><strong>Add supporting document</strong><span>Word, PDF, JPG or PNG · up to 5 MB each</span><input id="support-file" type="file" accept=".pdf,.docx,.jpg,.jpeg,.png" onChange={e=>{const file=e.target.files?.[0];e.target.value='';if(file)void upload(file,'supporting')}}/></label></details>{uploading&&<p role="status">Uploading file…</p>}
  </>}
  {step===2&&<><p>Check your details and proposal before submitting. Submitted applications cannot be changed.</p>
   {reviewSection(0,'Idea details',<dl>{Object.entries({Applicant:account?.name,'Idea title':values.title,Programme:programmes.find(p=>p.id===values.programme_id)?.name,Category:values.category,Location:values.location,Summary:values.summary}).map(([label,value])=><div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}</dl>)}
   {reviewSection(1,'Business proposal',proposal?<p><a href={`/api/v1/attachments/${proposal.id}`}>{proposal.name}</a><br/><small>{Math.ceil(proposal.size/1024)} KB · {values.category}</small></p>:<p className="idea-field-error">No matching proposal uploaded.</p>)}
   {supporting.length>0&&reviewSection(1,'Supporting documents',<ul>{supporting.map(f=><li key={f.id}>{f.name}</li>)}</ul>)}
   <label className="idea-declaration"><input type="checkbox" checked={declaration} onChange={e=>{setDeclaration(e.target.checked);setErrors({})}} {...fieldProps('declaration')}/><span>I confirm that the information is accurate to the best of my knowledge and that I am authorised to submit this idea and proposal for review.</span></label>{fieldError('declaration')}
  </>}
  </fieldset><div className="idea-wizard-actions"><button className="btn" type="button" disabled={step===0||busy||uploading} onClick={()=>go(step-1)}>Back</button><button className="btn" type="button" disabled={busy||uploading} onClick={()=>void persist().catch(()=>{})}>Save draft</button><button className="btn primary" disabled={busy||uploading||!programmes.length} type="submit">{busy?'Saving…':step===2?'Submit application':'Continue'}</button></div></form>
 </div>;
}

export default function ApplicantForm(props:Parameters<typeof DocumentApplicantForm>[0]){const schema=props.application.form_schema??props.definitions?.[props.application.programme_id||props.programmes[0]?.id]??props.definition;return schema.mode==='document'?<DocumentApplicantForm {...props}/>:schema.layout==='smart-v1'?<SmartApplicantForm {...props}/>:<OnlineApplicantForm {...props}/>}
