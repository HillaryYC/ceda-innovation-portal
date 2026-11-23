# CEDA Innovation Management Portal
## Functional and design specification

**Draft for stakeholder review · 23 November 2026**

## 1. What the portal is for

The CEDA Innovation Management Portal brings the public website, innovation submissions and staff review work into one connected service. A visitor can learn about the programme and create an account. An applicant can develop an idea over several sessions and follow its progress. CEDA staff can manage access, review submitted ideas and keep a record of the assessment process.

The design was shaped by practical use on both a phone and a laptop. This document describes what people can do, why important design choices were made, and which services still need final operational setup.

## 2. People who use the portal

| User | What they do |
| --- | --- |
| Public visitor | Reads about the programme, views awarded ideas, asks questions and opens the account portal. |
| Applicant | Creates an account, completes an innovation submission, uploads supporting evidence and follows its progress. |
| Evaluator | Reads an assigned submission and records an independent assessment against CEDA's criteria. |
| Programme manager | Oversees applications in an authorised programme, manages reviewers and creates the criteria templates used in evaluation. |
| Master administrator | Manages staff accounts, roles and access across programmes, including sensitive account actions. |

The adjudicator reviews completed evaluator assessments and records a reasoned final decision in a dedicated workstation. The decision remains a draft until required scores, an outcome and a rationale are submitted.

## 3. Features for visitors and applicants

### Public website

Visitors are greeted by a website with a clear route to sign in or create an account. The site explains how to submit an innovative idea and showcases ideas that have received awards.

**Why we designed it this way:** The website should show both the opportunity and what the programme has produced. The hero image features a businesswoman using a phone, helping visitors picture the programme as accessible to everyday business owners. The awarded ideas give concrete examples of outcomes, while the process section makes the path to applying easier to understand.

### Ask CEDA chatbot

**Ask CEDA** is the chatbot available on the public website and in the portal. Visitors and applicants can ask it about the programme and the application process. At present, CEDA staff publish approved questions and answers for it to use. The proposed improvement is a programme knowledge library: an administrator uploads an approved PDF or Word document explaining what the programme supports, eligibility, application steps and other relevant rules. Ask CEDA would search the relevant passages and answer in plain language, showing which part of the document supports its answer. Staff would no longer need to predict every question in advance.

An administrator should be able to review a new document, try sample questions and publish a new version when satisfied. Frequently changing facts, such as deadlines, contact details and whether applications are open, should remain easy to update directly. If the approved material does not support an answer, Ask CEDA should say it cannot confirm the information and escalate the conversation to a staff member assigned to handle enquiries instead of guessing. That person should receive the chat history and the reason for the handoff. Sensitive questions and repeated feedback that an answer was unhelpful can also trigger a handoff. Document upload and search have not yet been built, and the live human handoff integration is currently disabled.

**Why we designed it this way:** A programme document gives Ask CEDA a wider, more flexible source than a fixed list of anticipated questions. Showing the source helps visitors judge an answer and helps staff correct outdated information. A person remains responsible for questions the published material cannot answer, and passing the conversation context avoids asking the visitor to start again. The chatbot should explain published rules but should not decide an individual applicant's eligibility or access private application records.

### Account access

Applicants use a shared sign-in page and then enter their own dashboard. In the current hackathon deployment, applicants sign in with an email address and password; the SMS challenge is disabled by configuration. Email or SMS codes can also be used for account verification and recovery where the account is eligible for that channel. Authenticator MFA remains supported for staff. The current deployment temporarily disables it for the master administrator and accounts with only evaluator or adjudicator roles; other staff roles still require it.

**Why we designed it this way:** The same entry point is easy to recognise, while different account types receive the protection appropriate to their work. When an email address or password is wrong, the portal says **“Invalid email or password”** without revealing which detail was incorrect. It also temporarily limits repeated unsuccessful attempts, making it harder for someone to keep guessing account details. Email and SMS delivery require the relevant providers to be configured for live use.

### Innovation submission

Innovation submissions are completed through a multi-step form. Once an applicant chooses the business category for which they seek funding, the form presents questions relevant to that category and the stage of the business. For example, a person with an early concept is asked about testing the idea, while an operating business is asked for evidence of customers and activity. The form also collects the proposed funding needs and ends with a review step.

Applicants can save their work, leave and continue later. The system checks that required answers and, where applicable, required documents are present before it accepts a final submission. These checks help prevent incomplete forms; they do not assess the quality or truth of the business proposal. Once submitted, the application becomes a read-only record. If CEDA later changes the form, an existing draft keeps the version it started with.

**Why we changed the form:** The first design used one long vertical form. When we tried the application experience, its length made completion feel discouraging. We changed it to shorter steps and category-specific questions so applicants can focus on the information relevant to their innovation instead of working through unrelated questions.

### Supporting documents

Applicants can upload supporting documents for the innovation they submit, subject to the portal's accepted file types and size limits. The earlier document-based route also provides a category-specific proposal template and requires the completed proposal file. Files are kept private and are available only to the applicant and staff authorised to work on that application.

### Applicant dashboard and progress

The applicant dashboard puts the idea title, application reference, current stage and next action in view. A draft offers **Continue draft**; a submitted idea opens as a record that can be read but not casually changed. Stages are written in words, so applicants do not have to infer meaning from colours alone.

## 4. Features for CEDA staff

### Master administration and programme management

The master administrator manages staff accounts and assigns access to the appropriate programmes and duties. Programme managers work within the programmes they are authorised to manage. They can inspect applications, manage reviewer allocation, maintain Ask CEDA's approved information and create evaluation criteria templates. A criteria template defines what evaluators mark and the maximum points for each criterion. In the proposed document-library workflow, authorised staff would upload, test, publish and replace programme guidance documents.

**Why we designed it this way:** Staff should see and change only the work they are responsible for. Criteria templates also make reviews more consistent and keep a record of the questions and scoring rules used for a particular assessment.

### Application checks and review allocation

The portal checks whether a submission contains the required information before it enters the review queue. It then attempts to match the application with an available evaluator whose selected expertise fits the applicant's business category. The matching process considers current workload and rotation. If no suitable evaluator or active criteria template is available, the application stays visible to staff with a reason for the delay. A programme manager can assign a reviewer manually.

### Independent evaluation and AI summaries

An evaluator reads the submitted information and scores it against the assigned criteria template. Work can be saved before the assessment is submitted. An optional AI summary condenses the applicant's information so the evaluator can find the main points more quickly. The evaluator must still read the source material and make the assessment. AI does not award points, recommend funding or make a final decision.

**Why we limited AI's role:** A summary is useful when an application contains a lot of information, but the decision must remain accountable to a person using CEDA's criteria. Before the business idea is sent to the AI provider, the portal removes account details and attachments and masks known personal identifiers in the proposal text. This de-identification is intended to keep the applicant's personal information out of the provider's systems: the AI receives the idea content needed for a summary, not the applicant's account record. The evaluator checks the text before sending it because an applicant may still reveal their identity in a written answer. The AI request also asks the provider not to store the response. These measures reduce exposure but cannot guarantee that every identifying detail has been removed.

### Adjudication workflow and current status

The adjudicator sees an assigned application after all active evaluators have submitted their assessments. The workstation presents the original application, authorised evidence and evaluator findings. The adjudicator scores each criterion, records notes, selects an outcome and explains the decision. Drafts can be saved; a submitted decision is read-only and timestamped.

**Production record on 23 November 2026:** Five adjudication assignments exist, all in pending status. None has a recorded recommendation or submitted decision. This is a workflow status report, not an assessment of the applications or an award recommendation.

### Reporting and awarded ideas

Staff can view application and outcome reports based on portal records. An awarded idea appears on the public website only after an authorised publication action. This keeps public success stories connected to real outcomes rather than demonstration figures.

## 5. Design choices shaped by use

### Configurable forms and review criteria

We designed the portal so CEDA administrators can adjust key parts of the process without rebuilding the website each time the programme changes. Administrators can create and revise criteria templates to reflect what CEDA wants evaluators to assess and how many points each criterion carries. They can also configure business categories and the questions shown in the application form for each category. An applicant therefore sees questions suited to the type of business they selected, while CEDA can refine those questions as its requirements develop.

**Why we designed it this way:** Programme rules and priorities can change. Giving administrators control over forms and scoring criteria makes the portal useful beyond one application round. Changes are published as new versions, so an existing application or assessment keeps the questions and criteria it was originally based on. This protects the fairness and traceability of earlier decisions.

### Comfortable viewing for long sessions

We tested the portal with one person using a phone and another using a laptop. The laptop user remarked that the screen felt as though a night-light setting was on because it was comfortable to look at for an extended period. We had initially followed CEDA's colours for brand consistency. That feedback made us pay closer attention to the warm brown, cream and soft orange surfaces and how they feel during long staff sessions. We retained that calmer palette while keeping text readable and controls clear.

This was valuable user feedback, not a medical claim that the interface prevents eye strain. The design aim is to avoid harsh, tiring surfaces for staff who may review applications for hours at a time.

### Clear and responsive journeys

The public site, applicant form and staff workspace share a recognisable CEDA identity. The multi-step form, navigation and assistant were checked on a narrow phone screen as well as a laptop screen. The assistant launcher was moved so it does not cover the form's Continue or Submit controls. Status messages use words as well as colour, and keyboard focus should remain visible.

## 6. Security and privacy measures

The portal checks access on the server for each role and programme. Applicants can access their own submissions and files; staff access depends on their assigned duties. Uploaded documents are private. Sensitive staff actions can require a fresh authenticator code. Account recovery codes expire after a short period and can be used only once. The system records important administrative and review actions so changes can be traced.

Applicant sign-in currently uses an email address and password while the SMS challenge is disabled. Email and SMS verification or recovery require configured delivery providers. Authenticator MFA is supported for staff, with temporary hackathon exceptions for the master and reviewer-only roles. Repeated incorrect sign-in attempts are temporarily limited, and the error message does not disclose whether the account email or password was wrong.

For AI summaries, the provider receives de-identified idea content rather than the applicant's account details or uploaded files. Access is limited to the assigned evaluator, and summaries saved in the portal are encrypted. Human review remains necessary because automatic masking cannot identify every personal detail inside an applicant's narrative.

## 7. Technology and operational readiness

The interface is built with React and TypeScript. A Laravel API applies the form, access and review rules, with PostgreSQL holding the operational records. The application is deployed on Railway. Forms and criteria are versioned so changes do not rewrite the basis of an earlier submission or assessment.

The codebase has automated tests and documented checks of key applicant, staff and mobile journeys. Live use still depends on confirming email and SMS delivery, background workers, scheduled tasks, database backups and deployment checks. The AI service needs a live output review. The proposed Ask CEDA document library needs upload controls, document processing, source-linked answers, versioning and staff testing before publication. Chat escalation must be connected to the staff team responsible for enquiries and tested to confirm that the assigned person receives the conversation, its context and the handoff reason.

CEDA should confirm the final eligibility rules, application wording, scoring criteria and outcome process before a wider release. A complete acceptance exercise should follow one application from account creation and draft saving through submission, allocation, assessment and communication of the outcome.
