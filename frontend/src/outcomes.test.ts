import {it,expect} from 'vitest';
import {seed,type Idea} from './model';
import {categoryOutcomes,outcomeGroup,outcomeRecordsCsv,outcomeCategoriesCsv} from './outcomes';
it('counts only final awards as success and adjudicator rejection as failure',()=>{
 expect(outcomeGroup({...seed[0],outcome:'awarded'})).toBe('successful');
 expect(outcomeGroup({...seed[0],outcome:'adjudicator_rejected'})).toBe('unsuccessful');
 expect(outcomeGroup({...seed[0],outcome:'approved'})).toBe('unrecorded');
 expect(outcomeGroup({...seed[0],status:'ineligible'})).toBe('unrecorded');
 expect(outcomeGroup({...seed[0],outcome:'not_awarded'})).toBe('other');
});
it('groups results by category with an explicit outcome denominator',()=>{
 const records:Idea[]=['awarded','adjudicator_rejected','approved','not_awarded'].map(outcome=>({...seed[0],outcome:outcome as Idea['outcome']}));
 expect(categoryOutcomes(records)[0]).toMatchObject({total:4,successful:1,unsuccessful:1,remaining:1,other:1,successRate:.5});
 expect(categoryOutcomes([seed[0]])[0].successRate).toBeNull();
});
it('exports category totals and final outcome labels without changing inputs',()=>{
 const records:Idea[]=[{...seed[0],outcome:'awarded'},{...seed[1],outcome:'adjudicator_rejected'}];
 expect(outcomeRecordsCsv(records)).toContain('Rejected by adjudicator');
 expect(outcomeCategoriesCsv(records)).toContain('Successful (awarded)');
 expect(outcomeCategoriesCsv(records)).toContain('100%');
 expect(records[0].outcome).toBe('awarded');
});
