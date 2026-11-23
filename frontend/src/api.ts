/** Same-origin PHP API contract. This client does not silently fall back to demo data. */
export interface ApiApplication {id:number;reference:string;programme_id:number;title:string;applicant_name:string;location:string;category:string;submitted_at:string;stage:string;completeness:{result:'complete'|'missing_information';source:string};summary:string;problem:string;solution:string}
export interface ApiPage<T>{data:T[];meta:{current_page:number;last_page:number;per_page:number;total:number};links:{next:string|null;prev:string|null}}
export interface SessionUser{id:number;name:string;email:string}
let csrfToken='';
export class ApiError extends Error {constructor(public status:number,message:string,public errors?:Record<string,string[]>){super(message)}}
async function request<T>(path:string,init:RequestInit={}):Promise<T>{
 const response=await fetch('/api/v1'+path,{...init,credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json',...(csrfToken?{'X-CSRF-TOKEN':csrfToken}:{}),...init.headers}});
 if(response.status===204)return undefined as T;
 const body=await response.json();if(!response.ok)throw new ApiError(response.status,body.message||'The request could not be completed.',body.errors);return body as T;
}
export async function login(email:string,password:string){csrfToken=(await request<{csrf_token:string}>('/csrf')).csrf_token;const result=await request<{user:SessionUser}>('/login',{method:'POST',body:JSON.stringify({email,password})});csrfToken=(await request<{csrf_token:string}>('/csrf')).csrf_token;return result.user;}
export const currentUser=()=>request<{user:SessionUser}>('/me');
export async function logout(){await request<void>('/logout',{method:'POST'});csrfToken='';}
export const getProgrammes=()=>request<{data:{id:number;name:string;code:string}[]}>('/programmes');
export const getApplications=(filters:Record<string,string|number>={})=>request<ApiPage<ApiApplication>>('/applications?'+new URLSearchParams(Object.entries(filters).map(([k,v])=>[k,String(v)])));
export const getApplication=(id:number)=>request<{data:ApiApplication}>('/applications/'+id);
