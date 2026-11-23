// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,fireEvent,render,screen,within} from '@testing-library/react';
import ApplicantPortal from './ApplicantPortal';
import {api} from './operationalApi';
vi.mock('./operationalApi',()=>({api:vi.fn()}));
afterEach(()=>{cleanup();vi.clearAllMocks()});
it('opens mobile navigation and navigates to applicant account details',async()=>{
 HTMLDialogElement.prototype.showModal=vi.fn(function(this:HTMLDialogElement){this.setAttribute('open','')});
 vi.mocked(api).mockImplementation(async path=>path==='me'?{user:{name:'Applicant One',email:'applicant@example.test'}} as never:{data:[],programmes:[]} as never);
 render(<ApplicantPortal signedIn/>);
 const tabs=screen.getByRole('navigation',{name:'Applicant sections'});
 expect(within(tabs).queryByRole('button',{name:'Settings'})).toBeNull();
 expect(within(tabs).queryByRole('button',{name:'History'})).toBeNull();
 fireEvent.click(screen.getByRole('button',{name:'Open navigation'}));
 const menu=screen.getByRole('dialog',{name:'Applicant menu'});
 fireEvent.click(within(menu).getByRole('button',{name:'Settings'}));
 expect(await screen.findByText('applicant@example.test')).toBeTruthy();
 expect(screen.queryByRole('dialog')).toBeNull();
 const settingsTabs=screen.getByRole('navigation',{name:'Applicant sections'});
 expect(within(settingsTabs).getByRole('button',{name:'Settings'}).getAttribute('class')).toContain('active');
 expect(within(settingsTabs).queryByRole('button',{name:'My Applications'})).toBeNull();
 expect(screen.getByRole('button',{name:'Open navigation'}).getAttribute('aria-expanded')).toBe('false');
});
it('shows submitted applications in history without including drafts and opens their saved content',async()=>{
 const common={category:'Agriculture',location:'Kang',programme_id:1,summary:'Summary',problem:'Problem',solution:'Solution'};
 vi.mocked(api).mockImplementation(async path=>path==='me'?{user:{name:'Applicant One',email:'applicant@example.test'}} as never:{data:[{...common,id:1,title:'Submitted idea',status:'assigned',submitted_at:'2026-11-18'},{...common,id:2,title:'Unsubmitted draft',status:'draft'}],programmes:[]} as never);
 render(<ApplicantPortal signedIn/>);await screen.findByRole('heading',{name:'Submitted idea'});
 fireEvent.click(screen.getByRole('button',{name:'Open navigation'}));
 fireEvent.click(within(screen.getByRole('dialog',{name:'Applicant menu'})).getByRole('button',{name:'History'}));
 expect(within(screen.getByRole('navigation',{name:'Applicant sections'})).getByRole('button',{name:'History'}).getAttribute('class')).toContain('active');
 expect(screen.queryByText('Unsubmitted draft')).toBeNull();expect(screen.getByText('In review')).toBeTruthy();
 fireEvent.click(screen.getByRole('button',{name:'View Submitted idea'}));
 expect(screen.getByText('Submission overview')).toBeTruthy();
});
it('asks before deleting a draft and removes it after the API succeeds',async()=>{
 const confirm=vi.spyOn(window,'confirm').mockReturnValue(false);
 vi.mocked(api).mockImplementation(async path=>path==='me'?{user:{name:'Applicant One',email:'applicant@example.test'}} as never:path==='applicant/applications/2'?{} as never:{data:[{id:2,title:'Draft idea',category:'Poultry',location:'Maun',programme_id:1,summary:'Idea',problem:'Problem',solution:'Solution',status:'draft'}],programmes:[]} as never);
 render(<ApplicantPortal signedIn/>);
 fireEvent.click(screen.getByRole('button',{name:'Drafts'}));
 fireEvent.click(await screen.findByRole('button',{name:'Delete Draft idea draft'}));
 expect(confirm).toHaveBeenCalledOnce();
 expect(vi.mocked(api)).not.toHaveBeenCalledWith('applicant/applications/2','DELETE');
 confirm.mockReturnValue(true);
 fireEvent.click(screen.getByRole('button',{name:'Delete Draft idea draft'}));
 expect(await screen.findByText('Draft deleted.')).toBeTruthy();
 expect(screen.queryByText('Draft idea')).toBeNull();
 confirm.mockRestore();
});

