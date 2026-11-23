export type ApplicantApplication = {
 id:number; title:string; category:string; location:string; programme_id:number;
 summary:string; problem:string; solution:string; status:string; submitted_at?:string|null;
 form_schema?:import('./ApplicantForm').FormDefinition;form_answers?:import('./ApplicantForm').FormAnswers;draft_step?:number;attachments?:import('./ApplicantForm').Attachment[];saved_fingerprint?:string;
 information_requests?:{id:number;source:'evaluation'|'adjudication';question:string;status:'open'|'responded';response:string|null;created_at:string;responded_at:string|null;attachment_id?:number|null;attachment_name?:string|null}[];
};

const stages:Record<string,{label:string;message:string;tone:string}> = {
 draft:{label:'Draft',message:'Continue your draft when you’re ready to submit.',tone:'draft'},
 ready:{label:'Submitted',message:"We've received your application. It's waiting to be assigned to a reviewer.",tone:'submitted'},
 pending:{label:'Submitted',message:'Your application is awaiting review.',tone:'submitted'},
 assigned:{label:'In review',message:'Your application has been allocated for review.',tone:'review'},
 information:{label:'Action required',message:'CEDA has asked for more information. Open this idea to respond.',tone:'attention'},
 ineligible:{label:'Closed',message:'This application is closed. Contact the programme team for details.',tone:'closed'},
};
export function applicantStage(status:string){return stages[status]??{label:'Submitted',message:'Your application has been received.',tone:'submitted'}}
export function applicantDate(date?:string|null){
 const match=date?.match(/^(\d{4})-(\d{2})-(\d{2})/);
 return match?`${match[3]}/${match[2]}/${match[1].slice(-2)}`:'Not available';
}

export default function ApplicantSubmissionCard({application,programme,onOpen,onDelete}:{application:ApplicantApplication;programme?:string;onOpen:()=>void;onDelete?:()=>void}){
 const stage=applicantStage(application.status);
 return <article className="applicant-submission-card" aria-labelledby={`applicant-idea-${application.id}`}>
  <div className="applicant-card-top"><span className="applicant-reference">IDEA-{application.id}</span><span className={`applicant-stage ${stage.tone}`}>{stage.label}</span></div>
  <h3 id={`applicant-idea-${application.id}`}>{application.title||'Untitled idea'}</h3>
  {programme&&<p className="applicant-programme">{programme}</p>}
  {application.summary&&<p className="applicant-summary">{application.summary}</p>}
  <dl className="applicant-card-meta"><div><dt>Category</dt><dd>{application.category}</dd></div><div><dt>{application.status==='draft'?'Submission':'Submitted'}</dt><dd>{application.status==='draft'?'Not submitted':applicantDate(application.submitted_at)}</dd></div></dl>
  <div className="applicant-card-footer"><p>{stage.message}</p><div className="applicant-card-actions"><button className="btn primary" onClick={onOpen}>{application.status==='draft'?'Continue draft':application.information_requests?.some(request=>request.status==='open')?'Respond to request':'View application'}</button>{application.status==='draft'&&onDelete&&<button className="applicant-delete-draft" type="button" aria-label={`Delete ${application.title||'untitled idea'} draft`} title="Delete draft" onClick={onDelete}><Trash size={20} aria-hidden="true"/></button>}</div></div>
 </article>;
}
import {Trash} from '@phosphor-icons/react';
