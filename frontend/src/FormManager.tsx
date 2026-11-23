import {useEffect,useState,type FormEvent} from 'react';
import {api} from './operationalApi';
import type {FormDefinition} from './ApplicantForm';
import './form-manager.css';

type Question=FormDefinition['categories'][string]['questions'][number];
type Category={name:string;description:string;questions:Question[];family?:string};
type Catalog={data:{programme_id:number;definition:FormDefinition}[];programmes:{id:number;name:string}[]};
const answerTypes:[NonNullable<Question['type']>,string][]=[['text','Short text'],['textarea','Long answer'],['number','Number'],['select','Choose one option'],['multiselect','Choose several options'],['file','Upload a file']];

export default function FormManager(){
 const [data,setData]=useState<Catalog|null>(null);
 const [programmeId,setProgrammeId]=useState(0);
 const [stages,setStages]=useState<string[]>([]);
 const [categories,setCategories]=useState<Category[]>([]);
 const [selected,setSelected]=useState<number|null>(null);
 const [error,setError]=useState(''),[notice,setNotice]=useState(''),[busy,setBusy]=useState(false);

 function loadProgramme(id:number,source:Catalog|null=data){
  const definition=source?.data.find(item=>item.programme_id===id)?.definition;
  if(!definition)return;
  setProgrammeId(id);setStages([...definition.stages]);
  setCategories(Object.entries(definition.categories).map(([name,category])=>({name,description:category.description,family:category.family,questions:category.questions.map(question=>({...question,stages:[]}))})));
  setSelected(null);setError('');setNotice('');
 }
 useEffect(()=>{api<Catalog>('application-forms').then(result=>{setData(result);loadProgramme(result.programmes[0]?.id,result)}).catch(e=>setError((e as Error).message))},[]);
 function updateCategory(index:number,patch:Partial<Category>){setCategories(items=>items.map((item,i)=>i===index?{...item,...patch}:item))}
 function updateQuestion(index:number,patch:Partial<Question>){if(selected===null)return;updateCategory(activeIndex,{questions:categories[selected].questions.map((question,i)=>i===index?{...question,...patch}:question)})}
 function renameStage(index:number,name:string){setStages(items=>items.map((stage,i)=>i===index?name:stage))}
 function removeStage(index:number){setStages(items=>items.filter((_,i)=>i!==index))}
 function addCategory(){setCategories(items=>[...items,{name:'',description:'',questions:[{key:'details',label:'',hint:'',required:true,max:3000,type:'textarea'}]}]);setSelected(categories.length);setError('')}
 function addQuestion(){if(selected===null)return;const questions=categories[selected].questions;let number=questions.length+1;while(questions.some(question=>question.key===`question_${number}`))number++;updateCategory(activeIndex,{questions:[...questions,{key:`question_${number}`,label:'',hint:'',required:true,max:3000,type:'textarea'}]})}
 function validate(){
  if(!stages.length||stages.some(stage=>!stage.trim())||new Set(stages.map(stage=>stage.trim())).size!==stages.length){setSelected(null);setError('Add a different name for each development stage.');return false}
  for(let index=0;index<categories.length;index++){
   const category=categories[index];
   if(!category.name.trim()||!category.description.trim()||!category.questions.length||category.questions.some(question=>!question.label.trim())){
    setSelected(index);setError(`Complete the category name, description and all question titles for ${category.name||'the new category'}.`);return false;
   }
   if(category.questions.some(question=>['select','multiselect'].includes(question.type||'')&&(!question.options||question.options.length<2||question.options.some(option=>!option.trim())||new Set(question.options.map(option=>option.trim().toLowerCase())).size!==question.options.length))){setSelected(index);setError(`Add at least two different answer choices for each choice question in ${category.name}.`);return false}
  }
  return true;
 }
 async function publish(event:FormEvent<HTMLFormElement>){event.preventDefault();if(!validate())return;if(!confirm('Publish these forms for new applications? Every question in a category will appear at every development stage. Existing applications will keep their current forms.'))return;setBusy(true);setError('');setNotice('');try{
  const result=await api<{definition:FormDefinition}>(`application-forms/${programmeId}`,'POST',{stages,categories});
  setData(current=>current?{...current,data:current.data.map(item=>item.programme_id===programmeId?{...item,definition:result.definition}:item)}:current);
  setNotice('Forms published. New applications will use this version.');setSelected(null);
 }catch(e){setError((e as Error).message)}finally{setBusy(false)}}

 const activeIndex=selected??0;
 const current=selected===null?null:categories[activeIndex];
 return <section className="connected-form form-manager">
  <header className="form-manager-heading"><div><span className="form-manager-kicker">APPLICATION SETUP</span><h2>Business category forms</h2><p>Choose a category to set up the questions applicants will answer.</p></div><span className="form-version">{data?.data.find(item=>item.programme_id===programmeId)?.definition.version}</span></header>
  {error&&<p role="alert" className="report-error">{error}</p>}{notice&&<p role="status" className="template-notice">{notice}</p>}
  {data&&<form className="form-manager-editor" onSubmit={publish} noValidate>
   <div className="form-manager-toolbar"><label>Programme<select value={programmeId} onChange={event=>loadProgramme(Number(event.target.value))}>{data.programmes.map(programme=><option key={programme.id} value={programme.id}>{programme.name}</option>)}</select></label><span>{categories.length} business categories</span></div>
   {current===null?<>
    <div className="form-manager-section-title"><div><h3>Choose a business category</h3><p>Each card opens the form applicants see for that type of business.</p></div></div>
    <details className="form-manager-stages"><summary><span>Development stages<small>These choices appear in every business category form.</small></span><span className="form-manager-stage-count">{stages.length} stages</span></summary><div className="form-manager-stage-list">{stages.map((stage,index)=><div className="form-editor-row" key={index}><label>Stage {index+1}<input value={stage} maxLength={100} onChange={event=>renameStage(index,event.target.value)}/></label><button className="btn" type="button" disabled={index===0} onClick={()=>setStages(items=>{const next=[...items];[next[index-1],next[index]]=[next[index],next[index-1]];return next})}>Move up</button><button className="btn" type="button" onClick={()=>removeStage(index)}>Remove</button></div>)}<button className="btn" type="button" onClick={()=>setStages(items=>[...items,''])}>Add stage</button></div></details>
    <div className="form-manager-card-grid"><button className="form-manager-card form-manager-new" type="button" onClick={addCategory}><span className="form-manager-new-icon" aria-hidden="true">+</span><strong>Add a business category</strong><span>Create a form for another type of business.</span></button>{categories.map((category,index)=><button className="form-manager-card" type="button" key={index} onClick={()=>{setSelected(index);setError('')}} aria-label={`Set up ${category.name||'new category'} form`}><span className="form-manager-card-icon" aria-hidden="true">{(category.name.trim()[0]||'?').toUpperCase()}</span><strong>{category.name||'New category'}</strong><span className="form-manager-card-description">{category.description||'Add a description for applicants.'}</span><span className="form-manager-card-footer"><span>{category.questions.length} {category.questions.length===1?'question':'questions'}</span><span>Set up form <span aria-hidden="true">→</span></span></span></button>)}</div>
   </>:<section className="form-manager-category" aria-label={`Set up ${current.name||'new category'} form`}>
    <button className="form-manager-back" type="button" onClick={()=>{setSelected(null);setError('')}}>← All business categories</button>
    <div className="form-manager-category-heading"><div><span className="form-manager-kicker">BUSINESS CATEGORY FORM</span><h3>{current.name||'New category'}</h3><p>Set up the name, description and questions applicants will see.</p></div></div>
    <div className="form-manager-fields"><label>Business category name<input value={current.name} maxLength={100} placeholder="For example, Agriculture" onChange={event=>updateCategory(activeIndex,{name:event.target.value})}/><small>This is the category applicants select.</small></label><label>Description for applicants<textarea value={current.description} maxLength={1000} rows={3} placeholder="Describe which businesses belong in this category." onChange={event=>updateCategory(activeIndex,{description:event.target.value})}/></label></div>
    <div className="form-manager-questions-heading"><div><h4>Questions for applicants</h4><p>Every applicant who chooses this category will see these questions.</p></div><button className="btn" type="button" onClick={addQuestion}>Add question</button></div>
    <div className="form-manager-question-list">{current.questions.map((question,index)=><details className="form-manager-question" key={index} ref={element=>{if(element&&!question.label)element.open=true}}><summary><span className="form-manager-question-number">{index+1}</span><span className="form-manager-question-title">{question.label||'New question'}<small>{answerTypes.find(([type])=>type===(question.type||'textarea'))?.[1]} · {question.required?'Answer required':'Answer optional'}</small></span><span aria-hidden="true">⌄</span></summary><div className="form-manager-question-fields"><label>Question applicants will see<input value={question.label} maxLength={300} placeholder="Write the question in plain language" onChange={event=>updateQuestion(index,{label:event.target.value})}/></label><label>Helpful explanation <small>Optional</small><input value={question.hint||''} maxLength={1000} placeholder="Add a short example or instruction if needed" onChange={event=>updateQuestion(index,{hint:event.target.value})}/></label><label>How should applicants answer?<select value={question.type||'textarea'} onChange={event=>{const type=event.target.value as Question['type'];updateQuestion(index,{type,...(type==='select'||type==='multiselect'?{options:question.options?.length?question.options:['','']}: {})})}}>{answerTypes.map(([type,label])=><option key={type} value={type}>{label}</option>)}</select></label>{(question.type==='select'||question.type==='multiselect')&&<div className="form-manager-options"><div><strong>Answer choices</strong><small>{question.type==='select'?'Applicants choose one answer.':'Applicants can tick more than one answer.'}</small></div>{(question.options||[]).map((option,optionIndex)=><div className="form-manager-option-row" key={optionIndex}><span className="form-manager-option-symbol" aria-hidden="true">{question.type==='select'?'○':'□'}</span><input aria-label={`Answer choice ${optionIndex+1}`} value={option} maxLength={150} placeholder={`Answer choice ${optionIndex+1}`} onChange={event=>updateQuestion(index,{options:(question.options||[]).map((item,i)=>i===optionIndex?event.target.value:item)})}/><button className="subtle" type="button" aria-label={`Remove answer choice ${optionIndex+1}`} onClick={()=>updateQuestion(index,{options:(question.options||[]).filter((_,i)=>i!==optionIndex)})}>Remove</button></div>)}<button className="btn" type="button" disabled={(question.options||[]).length>=50} onClick={()=>updateQuestion(index,{options:[...(question.options||[]),'']})}>Add answer choice</button></div>}<label className="form-manager-check"><input type="checkbox" checked={question.required} onChange={event=>updateQuestion(index,{required:event.target.checked})}/>Applicants must answer this question</label><button className="subtle" type="button" onClick={()=>updateCategory(activeIndex,{questions:current.questions.filter((_,i)=>i!==index)})}>Remove this question</button></div></details>)}</div>
    <button className="form-manager-retire" type="button" onClick={()=>{if(categories.length===1){setError('Keep at least one business category.');return}setCategories(items=>items.filter((_,index)=>index!==selected));setSelected(null);setError('')}}>Remove this category from future forms</button>
   </section>}
   <div className="form-manager-publish"><p>Changes are saved for applicants when you publish the new version.</p><button className="btn primary" disabled={busy}>{busy?'Publishing…':'Publish form changes'}</button></div>
  </form>}
 </section>;
}

