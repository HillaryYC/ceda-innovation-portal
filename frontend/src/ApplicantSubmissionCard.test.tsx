// @vitest-environment jsdom
import React from 'react';
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,fireEvent,render,screen} from '@testing-library/react';
import ApplicantSubmissionCard,{type ApplicantApplication} from './ApplicantSubmissionCard';
afterEach(cleanup);
const application:ApplicantApplication={id:101,title:'Morula Farm',category:'Agriculture',location:'Gaborone',programme_id:1,summary:'A livestock business.',problem:'Access to livestock.',solution:'Local production.',status:'assigned',submitted_at:'2026-11-18T00:00:00.000000Z'};
it('shows applicant-facing stage, reference and date with an action to open the submission',()=>{
 const onOpen=vi.fn();render(<ApplicantSubmissionCard application={application} programme="CEDA Innovation Programme" onOpen={onOpen}/>);
 expect(screen.getByText('In review')).toBeTruthy();expect(screen.getByText('18/11/26')).toBeTruthy();expect(screen.getByText('IDEA-101')).toBeTruthy();
 fireEvent.click(screen.getByRole('button',{name:'View application'}));expect(onOpen).toHaveBeenCalledOnce();
});
it('does not present a draft save date as a submission date',()=>{
 render(<ApplicantSubmissionCard application={{...application,status:'draft'}} onOpen={()=>{}}/>);
 expect(screen.getByText('Not submitted')).toBeTruthy();expect(screen.queryByText('18/11/26')).toBeNull();expect(screen.getByRole('button',{name:'Continue draft'})).toBeTruthy();
});
it('shows the new waiting message and a delete action only for drafts',()=>{
 const onDelete=vi.fn();
 const {rerender}=render(<ApplicantSubmissionCard application={{...application,status:'ready'}} onOpen={()=>{}} onDelete={onDelete}/>);
 expect(screen.getByText("We've received your application. It's waiting to be assigned to a reviewer.")).toBeTruthy();
 expect(screen.queryByRole('button',{name:'Delete Morula Farm draft'})).toBeNull();
 rerender(<ApplicantSubmissionCard application={{...application,status:'draft'}} onOpen={()=>{}} onDelete={onDelete}/>);
 fireEvent.click(screen.getByRole('button',{name:'Delete Morula Farm draft'}));
 expect(onDelete).toHaveBeenCalledOnce();
});
