// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,fireEvent,render,screen,waitFor} from '@testing-library/react';
import ApplicantForm,{type FormDefinition} from './ApplicantForm';
import {api} from './operationalApi';
vi.mock('./operationalApi',()=>({api:vi.fn(async()=>({data:{id:10}}))}));
afterEach(cleanup);
const q=(key:string,label:string,group:string,stages?:string[])=>({key,label,group,stages,hint:'',required:true,max:3000,type:'text' as const});
const schema:FormDefinition={version:'smart-test',mode:'online',layout:'smart-v1',stages:['Starting or testing','Operating business'],core:{about:[],idea:[],funding:[]},categories:{Poultry:{family:'Producers',description:'Chicken farming',questions:[q('test','What have you tried so far?','Your progress',['Starting or testing']),q('water','What water source is available?','Producers'),q('birds','How many birds are planned?','Poultry')]},Metalwork:{family:'Makers',description:'Welding',questions:[q('workshop','Where is the workshop?','Makers'),q('orders','What orders are in hand?','Metalwork')]}}};

it('keeps the step number separate from its label and hides team size for individuals',()=>{
 const about={...schema,core:{...schema.core,about:[{key:'applying_as',label:'How are you applying?',group:'About you',type:'select' as const,options:['Individual','Group'],required:true,max:500,hint:''},{key:'team_size',label:'How many people are in your team?',group:'About you',type:'number' as const,options:[],required:true,max:500,hint:''}]}};
 render(<ApplicantForm definition={about} programmes={[{id:1,name:'CEDA'}]} account={{name:'Applicant',email:'applicant@example.test'}} application={{}} onExit={()=>{}} onSubmitted={()=>{}}/>);
 const firstStep=screen.getByRole('button',{name:'1 About you'});
 expect(firstStep.querySelectorAll(':scope > span')).toHaveLength(2);
 expect(screen.getByLabelText('How many people are in your team? *')).toBeTruthy();
 fireEvent.change(screen.getByLabelText('How are you applying? *'),{target:{value:'Individual'}});
 expect(screen.queryByLabelText('How many people are in your team? *')).toBeNull();
 fireEvent.change(screen.getByLabelText('How are you applying? *'),{target:{value:'Group'}});
 expect(screen.getByLabelText('How many people are in your team? *')).toBeTruthy();
});

it('shows only the selected industry and stage questions in the five step form',()=>{
 render(<ApplicantForm definition={schema} programmes={[{id:1,name:'CEDA'}]} account={{name:'Applicant',email:'applicant@example.test'}} application={{}} onExit={()=>{}} onSubmitted={()=>{}}/>);
 expect(screen.getByText('Step 1 of 5')).toBeTruthy();
 fireEvent.change(screen.getByLabelText('District or town of the business'),{target:{value:'Maun'}});
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 fireEvent.change(screen.getByLabelText('Business name or idea title'),{target:{value:'Farm idea'}});
 fireEvent.change(screen.getByLabelText('Business stage'),{target:{value:'Starting or testing'}});
 fireEvent.change(screen.getByLabelText('One-line idea summary'),{target:{value:'Raise chickens'}});
 fireEvent.change(screen.getByLabelText('What problem do you solve, and for whom?'),{target:{value:'Local demand'}});
 fireEvent.change(screen.getByLabelText('What is your solution?'),{target:{value:'Local supply'}});
 fireEvent.change(screen.getByLabelText('Describe your customers'),{target:{value:'Households'}});
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 expect(screen.getByLabelText('What have you tried so far? *')).toBeTruthy();
 expect(screen.getByLabelText('How many birds are planned? *')).toBeTruthy();
 expect(screen.queryByText('Where is the workshop?')).toBeNull();
 fireEvent.click(screen.getByRole('button',{name:'Back'}));
 fireEvent.change(screen.getByLabelText('Industry or business type'),{target:{value:'Metalwork'}});
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 expect(screen.getByLabelText('Where is the workshop? *')).toBeTruthy();
 expect(screen.queryByText('How many birds are planned? *')).toBeNull();
});

it('keeps the active field focused when an autosave completes',async()=>{
 render(<ApplicantForm definition={schema} programmes={[{id:1,name:'CEDA'}]} account={{name:'Applicant',email:'applicant@example.test'}} application={{}} onExit={()=>{}} onSubmitted={()=>{}}/>);
 const field=screen.getByLabelText('District or town of the business') as HTMLInputElement;
 field.focus();
 fireEvent.change(field,{target:{value:'Maun'}});
 await waitFor(()=>expect(api).toHaveBeenCalledWith('applicant/applications','POST',expect.objectContaining({location:'Maun'})),{timeout:3000});
 await screen.findByText('Draft saved');
 expect(document.activeElement).toBe(field);
 fireEvent.change(field,{target:{value:'Maun West'}});
 expect(field.value).toBe('Maun West');
});
