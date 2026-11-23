import {useEffect,useState} from 'react';
import './workstation-attachment.css';
export type WorkstationFile={id:number;name:string;size:number;mime?:string};
export default function WorkstationAttachment({file,onClose}:{file:WorkstationFile;onClose:()=>void}){
 const textFile=file.mime?.startsWith('text/')||/\.(txt|docx|xlsx)$/i.test(file.name);
 const [content,setContent]=useState(''),[error,setError]=useState('');
 useEffect(()=>{if(!textFile)return;const controller=new AbortController();setContent('');setError('');fetch(`/api/v1/attachments/${file.id}/preview`,{credentials:'same-origin',signal:controller.signal}).then(async response=>{if(!response.ok)throw new Error('Document preview is unavailable.');setContent(await response.text())}).catch(cause=>{if(!controller.signal.aborted)setError((cause as Error).message)});return()=>controller.abort()},[file.id,textFile]);
 return <div className="workstation-file"><div className="workstation-file-head"><div><small>APPLICANT DOCUMENT</small><h2>{file.name}</h2></div><button type="button" onClick={onClose}>← All uploads</button></div>{textFile?<div className="workstation-file-text" role="document">{error?<p role="alert">{error}</p>:content||'Loading document…'}</div>:file.mime?.startsWith('image/')?<div className="workstation-file-image"><img src={`/api/v1/attachments/${file.id}/preview`} alt={`Uploaded document ${file.name}`}/></div>:<iframe title={`Preview of ${file.name}`} src={`/api/v1/attachments/${file.id}/preview`}/>}<p>{textFile?'Text is shown as a readable preview of the uploaded file.':file.mime?.startsWith('image/')?'Image shown at a readable size.':'Use the document viewer’s zoom controls to read small text.'}</p></div>;
}
