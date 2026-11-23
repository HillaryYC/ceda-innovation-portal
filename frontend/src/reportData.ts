import {statuses,type Idea} from './model';
export type ReportFilters={from:string;to:string;category:string;stage:string;reviewer:string;query:string};
export const emptyFilters:ReportFilters={from:'',to:'',category:'all',stage:'all',reviewer:'all',query:''};
export const stageLabel=(stage:string)=>statuses.find(([key])=>key===stage)?.[1]||stage;
export const displayDate=(date:string)=>date.split('-').reverse().map((v,i)=>i===2?v.slice(-2):v).join('/');
export function filterApplications(ideas:Idea[],f:ReportFilters){
 const q=f.query.trim().toLowerCase();
 return ideas.filter(i=>(!f.from||i.date>=f.from)&&(!f.to||i.date<=f.to)&&(f.category==='all'||i.category===f.category)&&(f.stage==='all'||i.status===f.stage)&&(f.reviewer==='all'||i.evaluators?.includes(f.reviewer))&&(!q||`${i.title} ${i.team} IDEA-${i.id}`.toLowerCase().includes(q)));
}
export function reviewRows(ideas:Idea[]){return ideas.flatMap(i=>(i.evaluators||[]).map(reviewer=>({idea:i,reviewer})));}
/** Quote every cell and neutralise spreadsheet formulas in user-provided text. */
export function csvText(rows:(string|number)[][]):string{return '\uFEFF'+rows.map(row=>row.map(value=>{const raw=String(value);const safe=/^[\s]*[=+@-]/.test(raw)?"'"+raw:raw;return '"'+safe.replace(/"/g,'""')+'"';}).join(',')).join('\r\n');}
export function applicationCsv(ideas:Idea[]){return csvText([['Reference','Application','Applicant name','Category','Submitted','Stage','Evaluators','Clarification'],...ideas.map(i=>[`IDEA-${i.id}`,i.title,i.team,i.category,displayDate(i.date),stageLabel(i.status),(i.evaluators||[]).join('; '),i.request?'Information requested':'None recorded'])]);}
export function reviewCsv(ideas:Idea[]){return csvText([['Reference','Application','Evaluator','Application stage','Assessment status','Due date','Completed date','Score'],...reviewRows(ideas).map(r=>[`IDEA-${r.idea.id}`,r.idea.title,r.reviewer,stageLabel(r.idea.status),'Not recorded','','',''])]);}
