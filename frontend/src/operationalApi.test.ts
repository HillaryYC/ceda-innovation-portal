// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
afterEach(()=>{vi.restoreAllMocks();vi.resetModules()});
const json=(data:unknown,status=200)=>new Response(JSON.stringify(data),{status,headers:{'Content-Type':'application/json'}});
it('deduplicates simultaneous saves and shares the CSRF request',async()=>{
 const fetch=vi.spyOn(globalThis,'fetch').mockImplementation(async url=>String(url).endsWith('/csrf')?json({csrf_token:'csrf'}):json({data:{id:3}}));
 const {api}=await import('./operationalApi');
 const a=api('people/3','PATCH',{action:'profile',expertise:'Farming'}),b=api('people/3','PATCH',{action:'profile',expertise:'Farming'});
 expect(a).toBe(b);await Promise.all([a,b]);
 expect(fetch.mock.calls.filter(([url])=>String(url).endsWith('/csrf'))).toHaveLength(1);
 expect(fetch.mock.calls.filter(([url])=>String(url).endsWith('/people/3'))).toHaveLength(1);
});
it('continues the original save with the same receipt key after MFA verification',async()=>{
 let attempted=0;
 const fetch=vi.spyOn(globalThis,'fetch').mockImplementation(async url=>String(url).endsWith('/csrf')?json({csrf_token:'csrf'}):String(url).endsWith('/mfa/verify')?json({message:'verified'}):++attempted===1?json({message:'Verify again'},423):json({data:{id:3}}));
 const {api}=await import('./operationalApi');
 const listener=(e:Event)=>{void api('mfa/verify','POST',{code:'123456'}).then(()=>(e as CustomEvent).detail.resolve())};window.addEventListener('ceda-step-up',listener);
 try{expect(await api('people/3','PATCH',{action:'access',role_ids:[2]})).toEqual({data:{id:3}});const calls=fetch.mock.calls.filter(([url])=>String(url).endsWith('/people/3'));expect(calls).toHaveLength(2);expect((calls[0][1]?.headers as Record<string,string>)['Idempotency-Key']).toBe((calls[1][1]?.headers as Record<string,string>)['Idempotency-Key'])}finally{window.removeEventListener('ceda-step-up',listener)}
});

it('refreshes CSRF after registration rotates the authenticated session',async()=>{
 const fetch=vi.spyOn(globalThis,'fetch').mockImplementation(async url=>String(url).endsWith('/csrf')?json({csrf_token:'fresh-token'}):json({message:'ok'}));
 const {api}=await import('./operationalApi');
 await api('applicant/register','POST',{email:'new@example.test'});
 await api('account-code','POST',{identity:'new@example.test',channel:'email',purpose:'verify'});
 expect(fetch.mock.calls.filter(([url])=>String(url).endsWith('/csrf'))).toHaveLength(2);
});
