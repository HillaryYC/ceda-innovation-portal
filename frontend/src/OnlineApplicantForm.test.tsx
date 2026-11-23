// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,fireEvent,render,screen} from '@testing-library/react';
import ApplicantForm,{type FormDefinition} from './ApplicantForm';
vi.mock('./operationalApi',()=>({api:vi.fn(async()=>({data:{id:10}}))}));
afterEach(cleanup);
const definition:FormDefinition={version:'online-v1',mode:'online',stages:['Concept','Prototype'],categories:{Agriculture:{description:'Food',questions:[{key:'trial',label:'What did the trial show?',hint:'Explain your evidence',required:true,max:3000,stages:['Prototype']}]}}};
it('asks only relevant stage questions and requires the online proposal instead of a document',()=>{
 render(<ApplicantForm definition={definition} programmes={[{id:1,name:'CEDA'}]} account={null} application={{title:'Farm',category:'Agriculture',location:'Maun',summary:'Grow food'}} onExit={()=>{}} onSubmitted={()=>{}}/>);
 fireEvent.change(screen.getByLabelText('Development stage'),{target:{value:'Concept'}});fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 expect(screen.getByLabelText('The problem')).toBeTruthy();expect(screen.queryByText('What did the trial show?')).toBeNull();expect(screen.queryByText('Upload completed proposal')).toBeNull();
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));expect(screen.getByRole('alert').textContent).toContain('required proposal');
 fireEvent.click(screen.getByRole('button',{name:'Back'}));fireEvent.change(screen.getByLabelText('Development stage'),{target:{value:'Prototype'}});fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 expect(screen.getByText(/What did the trial show/)).toBeTruthy();
});
