import {describe,it,expect} from 'vitest';
import {approve,assign,seed,evaluators,normaliseIdea,matchesApplicationTab} from './model';
describe('screening transitions',()=>{
 it('requires all checks and a complete submission',()=>{expect(()=>approve(seed[0])).toThrow();expect(()=>approve({...seed[1],checks:[true,true,true,true]})).toThrow();});
 it('approves a screened application without mutating it',()=>{const original={...seed[0],checks:[true,true,true,true]};expect(approve(original).status).toBe('ready');expect(original.status).toBe('pending');});
 it('requires approval before evaluator allocation',()=>{expect(()=>assign(seed[0],[evaluators[0][0]])).toThrow();});
 it('validates evaluators and records the allocation',()=>{const ready=approve({...seed[0],checks:[true,true,true,true]});expect(()=>assign(ready,[])).toThrow();expect(()=>assign(ready,['Unknown'])).toThrow();expect(assign(ready,[evaluators[0][0]])).toMatchObject({status:'assigned',evaluators:[evaluators[0][0]]});});
});

describe('automated completeness queues',()=>{
 it('routes complete and incomplete submissions automatically',()=>{expect(normaliseIdea(seed[0]).status).toBe('ready');expect(normaliseIdea(seed[1]).status).toBe('information');});
 it('keeps assigned records and includes every stage in All Applications',()=>{const record={...seed[0],status:'assigned' as const};expect(normaliseIdea(record).status).toBe('assigned');expect(matchesApplicationTab(record,'all')).toBe(true);expect(matchesApplicationTab(record,'ready')).toBe(false);});
 it('assigns complete submissions without a manual checklist',()=>{expect(assign(normaliseIdea(seed[0]),[evaluators[0][0]]).status).toBe('assigned');});
});
