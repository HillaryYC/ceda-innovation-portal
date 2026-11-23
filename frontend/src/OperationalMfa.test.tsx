// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,render,screen} from '@testing-library/react';
import Operational from './Operational';
import {api,type Session} from './operationalApi';
vi.mock('./operationalApi',()=>({api:vi.fn()}));
afterEach(()=>{cleanup();vi.clearAllMocks()});
const account:Session={id:2,name:'Reviewer',email:'reviewer@example.test',is_master:false,mfa_required:false,mfa_enrolled:false,mfa_verified:false,permissions:{'applications.view':[1]}};
it('does not show authenticator setup to external account holders',async()=>{vi.mocked(api).mockImplementation(async path=>path==='me'?{user:account} as never:{data:[],meta:{last_page:1,total:0}} as never);render(<Operational/>);await screen.findByRole('navigation',{name:'Main navigation'});expect(screen.queryByText('Verify your account')).toBeNull()});
it('requires authenticator setup for staff before the workspace',async()=>{vi.mocked(api).mockImplementation(async path=>path==='me'?{user:{...account,mfa_required:true}} as never:{data:[],meta:{last_page:1,total:0}} as never);render(<Operational/>);await screen.findByText('Enter your Authenticator code');expect(screen.queryByRole('navigation',{name:'Main navigation'})).toBeNull()});
