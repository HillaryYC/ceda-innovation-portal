export interface Staff {last_invitation?:{delivery_status:string;delivery_error:string|null;expires_at:string}|null;id:number;name:string;email:string;status:string;is_master:boolean;type:string;expertise:string|null;availability:string;expires_at:string|null;roles:{programme_id:number;role_id:number;name:string}[];overrides:{programme_id:number;permission:string;allowed:boolean}[]}
export interface Session {email_verified?:boolean;phone_verified?:boolean;onboarding_complete?:boolean;account_type?:string;id:number;name:string;email:string;is_master:boolean;mfa_required:boolean;mfa_enrolled:boolean;mfa_verified:boolean;permissions:Record<string,number[]>}
export interface Programme {id:number;name:string}
export interface PeopleData {data:Staff[];roles:{id:number;name:string}[];permissions:string[];programmes:Programme[]}
export interface ReviewTemplate {id:number;programme_id:number;name:string;kind:string;version:number;criteria:{name:string;max:number}[]|string;active:boolean;use_count?:number}
export interface Allocation {id:number;idea_id:number;user_id:number;kind:string;status:string;active:boolean;is_lead:boolean;due_date:string|null;title:string;name:string;template_name:string;template_version:number;template_id:number}
export interface ReviewsData {reviewers:{preferences?:{category:string;programme_id:number}[];id:number;name:string;email:string;expertise:string;availability:string;status:string;evaluator_programmes:number[];adjudicator_programmes:number[]}[];assignments:Allocation[];templates:ReviewTemplate[];applications:{id:number;title:string;team:string;programme_id:number;status:string;complete:number}[];programmes:Programme[]}
let token='';
let csrfRequest:Promise<string>|null=null;
const mutations=new Map<string,Promise<unknown>>();
const requestKeys=new Map<string,{key:string;until:number}>();
async function csrf(){
 if(token)return token;
 if(!csrfRequest)csrfRequest=fetch('/api/v1/csrf',{credentials:'same-origin',headers:{Accept:'application/json'}}).then(async r=>{if(!r.ok)throw new Error('Unable to verify this session. Please try again.');return token=(await r.json()).csrf_token}).finally(()=>{csrfRequest=null});
 return csrfRequest;
}
export function api<T>(path:string,method='GET',data?:unknown):Promise<T>{
 const key=JSON.stringify([path,method,data]);
 if(method!=='GET'&&mutations.has(key))return mutations.get(key) as Promise<T>;
 const prior=requestKeys.get(key);const receipt=prior&&prior.until>Date.now()?prior:{key:crypto.randomUUID(),until:Date.now()+60000};if(method!=='GET')requestKeys.set(key,receipt);
 const request=send<T>(path,method,data,false,receipt.key);
 void request.then(()=>requestKeys.delete(key),()=>{});
 if(method!=='GET'){mutations.set(key,request);void request.finally(()=>mutations.delete(key)).catch(()=>{})}
 return request;
}
async function send<T>(path:string,method:string,data:unknown,verified=false,requestKey=''):Promise<T>{
 if(method!=='GET')await csrf();
 const res=await fetch('/api/v1/'+path,{method,credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json',...(method!=='GET'?{'Idempotency-Key':requestKey}:{}),...(token?{'X-CSRF-TOKEN':token}:{})},...(data===undefined?{}:{body:JSON.stringify(data)})});
 if(res.status===204){token='';return undefined as T}
 const body=await res.json();
 if(res.status===423&&!verified&&path!=='mfa/verify'){
  await new Promise<void>((resolve,reject)=>window.dispatchEvent(new CustomEvent('ceda-step-up',{detail:{resolve,reject,purpose:(data as {action?:string})?.action==='reset_password'?'password_reset':undefined}})));
  return send<T>(path,method,data,true,requestKey);
 }
 if(!res.ok){if(res.status===401||res.status===419){token='';if(path!=='me'&&path!=='login')window.dispatchEvent(new Event('ceda-session-ended'))}throw new Error(body.errors?Object.values(body.errors).flat().join(' '):body.message||`Request failed (${res.status})`)}
 if(['login','activate','applicant/register','account-code/complete'].includes(path))token='';return body as T;
}

export async function uploadGuidanceDocument(programmeId:number,file:File,published:boolean):Promise<void>{
 const csrfToken=await csrf();const form=new FormData();form.append('programme_id',String(programmeId));form.append('document',file);form.append('published',published?'1':'0');
 const res=await fetch('/api/v1/guidance-documents',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':csrfToken,'Idempotency-Key':crypto.randomUUID()},body:form});
 if(!res.ok){const body=await res.json();throw new Error(body.errors?Object.values(body.errors).flat().join(' '):body.message||`Upload failed (${res.status})`)}
}
