import {expect,it} from 'vitest';
import {validateInvite} from './People';
it('rejects duplicate invitations regardless of email case',()=>{expect(()=>validateInvite('USER@example.test','Evaluator',[{email:'user@example.test',status:'Invited'}])).toThrow('already exists')});
it('prevents inviting privileged system roles',()=>{expect(()=>validateInvite('user@example.test','Master Administrator',[])).toThrow('permitted role')});
it('validates email and permits staff review invitations',()=>{expect(()=>validateInvite('invalid','Evaluator',[])).toThrow();expect(()=>validateInvite('user@example.test','Evaluator',[])).not.toThrow()});
