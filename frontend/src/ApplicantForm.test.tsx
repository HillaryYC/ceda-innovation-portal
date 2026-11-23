// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,fireEvent,render,screen,waitFor} from '@testing-library/react';
import ApplicantForm,{type FormDefinition,type Attachment} from './ApplicantForm';
import {api} from './operationalApi';
vi.mock('./operationalApi',()=>({api:vi.fn()}));
afterEach(()=>{cleanup();vi.clearAllMocks()});
const definition:FormDefinition={version:'business-proposal-v2',mode:'document',stages:[],categories:{Agriculture:{description:'Farming',template_slug:'agriculture',questions:[]},Health:{description:'Care',template_slug:'health',questions:[]}}};
const account={name:'Applicant',email:'applicant@example.test'},programmes=[{id:1,name:'CEDA'}];
const proposal:Attachment={id:3,name:'proposal.docx',size:1024,kind:'proposal',category:'Agriculture',template_version:definition.version};
const complete={id:12,category:'Agriculture',programme_id:1,title:'Farm',location:'Maun',summary:'Summary',form_schema:definition};
it('has three steps and permits incomplete draft saves',async()=>{
 vi.mocked(api).mockResolvedValue({data:{id:12,status:'draft'}} as never);
 render(<ApplicantForm application={{}} definition={definition} programmes={programmes} account={account} onExit={()=>{}} onSubmitted={()=>{}}/>);
 expect(screen.getByRole('button',{name:'3. Review & submit'})).toBeTruthy();expect(screen.queryByRole('button',{name:/4\./})).toBeNull();
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));expect(screen.getByText('Location is required.')).toBeTruthy();
 fireEvent.click(screen.getByRole('button',{name:'Save draft'}));
 await waitFor(()=>expect(api).toHaveBeenCalledWith('applicant/applications','POST',expect.objectContaining({submit:false,form_version:definition.version,location:'',draft_step:0})));
});
it('changes the template with category and rejects a proposal from the previous category',()=>{
 render(<ApplicantForm application={{...complete,attachments:[proposal]}} definition={definition} programmes={programmes} account={account} onExit={()=>{}} onSubmitted={()=>{}}/>);
 fireEvent.change(screen.getByLabelText('Category'),{target:{value:'Health'}});
 expect((screen.getByLabelText('Idea title') as HTMLInputElement).value).toBe('Farm');
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 expect(screen.getByRole('link',{name:'Download Word template'}).getAttribute('href')).toContain('/health?');
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));
 expect(screen.getByText('Upload your completed Word or PDF proposal for Health.')).toBeTruthy();
 expect(screen.queryByRole('heading',{name:'Review & submit'})).toBeNull();
});
it('requires a declaration then submits without requiring inline business questions',async()=>{
 const onSubmitted=vi.fn();vi.mocked(api).mockResolvedValue({data:{id:12,status:'ready'}} as never);
 render(<ApplicantForm application={{...complete,draft_step:2,attachments:[proposal]}} definition={definition} programmes={programmes} account={account} onExit={()=>{}} onSubmitted={onSubmitted}/>);
 fireEvent.click(screen.getByRole('button',{name:'Submit application'}));expect(screen.getByText('Confirm the declaration before submitting.')).toBeTruthy();expect(api).not.toHaveBeenCalled();
 fireEvent.click(screen.getByRole('checkbox'));fireEvent.click(screen.getByRole('button',{name:'Submit application'}));
 await waitFor(()=>expect(onSubmitted).toHaveBeenCalledOnce());
 expect(api).toHaveBeenCalledWith('applicant/applications/12','PUT',expect.objectContaining({submit:true,declaration:true,form_version:definition.version,problem:'',solution:''}));
});
it('uploads a Word proposal and allows progression to review',async()=>{
 vi.mocked(api).mockResolvedValue({data:proposal} as never);
 render(<ApplicantForm application={{...complete,draft_step:1}} definition={definition} programmes={programmes} account={account} onExit={()=>{}} onSubmitted={()=>{}}/>);
 const input=document.querySelector('#proposal-file')!;
 fireEvent.change(input,{target:{files:[new File(['document content'],'proposal.docx',{type:'application/vnd.openxmlformats-officedocument.wordprocessingml.document'})]}});
 await screen.findByRole('link',{name:'proposal.docx'});
 expect(api).toHaveBeenCalledWith('applicant/applications/12/attachments','POST',expect.objectContaining({kind:'proposal',category:'Agriculture',template_version:definition.version}));
 fireEvent.click(screen.getByRole('button',{name:'Continue'}));expect(screen.getByRole('heading',{name:'Review & submit'})).toBeTruthy();
});
it('autosaves once then uses the returned draft ID',async()=>{
 vi.mocked(api).mockResolvedValue({data:{id:25,status:'draft'}} as never);
 render(<ApplicantForm application={{}} definition={definition} programmes={programmes} account={account} onExit={()=>{}} onSubmitted={()=>{}}/>);
 fireEvent.change(screen.getByLabelText('Location'),{target:{value:'Maun'}});
 await waitFor(()=>expect(api).toHaveBeenCalledWith('applicant/applications','POST',expect.objectContaining({location:'Maun'})),{timeout:2500});await screen.findByText('Draft saved');
 fireEvent.change(screen.getByLabelText('Location'),{target:{value:'Kang'}});
 await waitFor(()=>expect(api).toHaveBeenCalledWith('applicant/applications/25','PUT',expect.objectContaining({location:'Kang'})),{timeout:2500});
 expect(vi.mocked(api).mock.calls.filter(([,method])=>method==='POST')).toHaveLength(1);
});
