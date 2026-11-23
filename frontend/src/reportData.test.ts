import {describe,it,expect} from 'vitest';
import {seed,normaliseIdea} from './model';
import {emptyFilters,filterApplications,csvText,applicationCsv,reviewCsv,reviewRows} from './reportData';
const ideas=seed.map(normaliseIdea);
describe('report filters and exports',()=>{
 it('applies inclusive date, category, stage and search filters together',()=>{const result=filterApplications(ideas,{...emptyFilters,from:'2026-11-12',to:'2026-11-12',category:'Agriculture',stage:'ready',query:'101'});expect(result.map(i=>i.id)).toEqual([101]);expect(filterApplications(ideas,{...emptyFilters,from:'2099-01-01'})).toEqual([])});
 it('filters applications by allocated evaluator',()=>{const assigned=ideas.find(i=>i.evaluators?.length)!;const name=assigned.evaluators![0];expect(filterApplications(ideas,{...emptyFilters,reviewer:name}).every(i=>i.evaluators?.includes(name))).toBe(true)});
 it('escapes CSV punctuation, newlines and formula-like values',()=>{expect(csvText([['a,b','a"b','a\nb','=SUM(A1)',' @cmd']])).toBe('\uFEFF"a,b","a""b","a\nb","\'=SUM(A1)","\' @cmd"')});
 it('exports all supplied records with formatted dates',()=>{const csv=applicationCsv(ideas);expect(csv).toContain('12/11/26');expect(csv).toContain('IDEA-101');expect(csv).toContain('IDEA-109');expect(csv.split('\r\n')).toHaveLength(ideas.length+1)});
 it('exports a row per evaluator allocation and no fabricated scores',()=>{const rows=reviewRows(ideas);expect(rows.length).toBeGreaterThan(0);const csv=reviewCsv(ideas);expect(csv.split('\r\n')).toHaveLength(rows.length+1);expect(csv).toContain('"Not recorded","","",""')});
});
