/* eslint-disabled */
import Contact from "./Contact";
import PipelineStep from "./PipelineStep";
import Tag from "./Tag";
import Activity from "./Activity";
import Asset from "./Asset";
import Note from "./Note";
import Company from "./Company";
import DealExtend from "./extends/DealExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Deal extends DealExtend {


public id = '';
public contactId = '';
public object = '';
public manager = '';
public stepId = '';
public tagsIds: string[] = [];
public participantsIds: string[] = [];
public activitiesIds: string[] = [];
public products? = '';
public assetsIds: string[] = [];
public notesIds: string[] = [];
public companyId = '';
public uuid? = '';
public createdAt?: Date;
public updatedAt?: Date;
public createBy? = '';
public updateBy? = '';
public removeAt?: Date;
public removeBy? = '';
public createdFromIp? = '';
public updatedFromIp? = '';
public restoredAt?: Date;
public restoredBy? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
   if(object.contactId){
       this.contactId = object.contactId
   }
   if(typeof object.contact === "string"){
       const occ = entityManager.get(object.contact, "Contact")
       if (occ && typeof occ === "object") {
           this.contactId = occ.id
       }else{
           this.contactId = object.contact
       }
   }else if(object.contact instanceof Contact){
       this.contactId = object.contact.id
       }else  if(object.contact && object.contact.id){
       this.contactId = object.contact.id
       const occ = new Contact(object.contact)
       }else  if(object.contact && entityManager.get(object.contact.id,"Contact") instanceof Contact){
       this.contactId = entityManager.get(object.contact.id,"Contact").id
       }
       this.object= object.object;
       this.manager= object.manager;
   if(object.stepId){
       this.stepId = object.stepId
   }
   if(typeof object.step === "string"){
       const occ = entityManager.get(object.step, "PipelineStep")
       if (occ && typeof occ === "object") {
           this.stepId = occ.id
       }else{
           this.stepId = object.step
       }
   }else if(object.step instanceof PipelineStep){
       this.stepId = object.step.id
       }else  if(object.step && object.step.id){
       this.stepId = object.step.id
       const occ = new PipelineStep(object.step)
       }else  if(object.step && entityManager.get(object.step.id,"PipelineStep") instanceof PipelineStep){
       this.stepId = entityManager.get(object.step.id,"PipelineStep").id
       }
       if(object.tagsIds){
               this.tagsIds= object.tagsIds
       }
       if(object.tags){
           object.tags.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Tag")
       if (found && typeof found === "object") {
               this.tagsIds.push(found.id);
       }else{
               this.tagsIds.push(occ);
       }
   }else{
               let tag= occ instanceof Tag? occ :   new Tag(occ);
       if (tag && !(tag instanceof Tag)) {
            tag = new Tag(tag)
       }
               entityManager.persist(tag)
               this.tagsIds.push(tag.id);
       }
           });
       }
       if(object.participantsIds){
               this.participantsIds= object.participantsIds
       }
       if(object.participants){
           object.participants.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Contact")
       if (found && typeof found === "object") {
               this.participantsIds.push(found.id);
       }else{
               this.participantsIds.push(occ);
       }
   }else{
               let contact= occ instanceof Contact? occ :   new Contact(occ);
       if (contact && !(contact instanceof Contact)) {
            contact = new Contact(contact)
       }
               entityManager.persist(contact)
               this.participantsIds.push(contact.id);
       }
           });
       }
       if(object.activitiesIds){
               this.activitiesIds= object.activitiesIds
       }
       if(object.activities){
           object.activities.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Activity")
       if (found && typeof found === "object") {
               this.activitiesIds.push(found.id);
       }else{
               this.activitiesIds.push(occ);
       }
   }else{
               let activity= occ instanceof Activity? occ :   new Activity(occ);
       if (activity && !(activity instanceof Activity)) {
            activity = new Activity(activity)
       }
               entityManager.persist(activity)
               this.activitiesIds.push(activity.id);
       }
           });
       }
       this.products= object.products;
       if(object.assetsIds){
               this.assetsIds= object.assetsIds
       }
       if(object.assets){
           object.assets.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Asset")
       if (found && typeof found === "object") {
               this.assetsIds.push(found.id);
       }else{
               this.assetsIds.push(occ);
       }
   }else{
               let asset= occ instanceof Asset? occ :   new Asset(occ);
       if (asset && !(asset instanceof Asset)) {
            asset = new Asset(asset)
       }
               entityManager.persist(asset)
               this.assetsIds.push(asset.id);
       }
           });
       }
       if(object.notesIds){
               this.notesIds= object.notesIds
       }
       if(object.notes){
           object.notes.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Note")
       if (found && typeof found === "object") {
               this.notesIds.push(found.id);
       }else{
               this.notesIds.push(occ);
       }
   }else{
               let note= occ instanceof Note? occ :   new Note(occ);
       if (note && !(note instanceof Note)) {
            note = new Note(note)
       }
               entityManager.persist(note)
               this.notesIds.push(note.id);
       }
           });
       }
   if(object.companyId){
       this.companyId = object.companyId
   }
   if(typeof object.company === "string"){
       const occ = entityManager.get(object.company, "Company")
       if (occ && typeof occ === "object") {
           this.companyId = occ.id
       }else{
           this.companyId = object.company
       }
   }else if(object.company instanceof Company){
       this.companyId = object.company.id
       }else  if(object.company && object.company.id){
       this.companyId = object.company.id
       const occ = new Company(object.company)
       }else  if(object.company && entityManager.get(object.company.id,"Company") instanceof Company){
       this.companyId = entityManager.get(object.company.id,"Company").id
       }
       this.uuid= object.uuid;
       if(object.createdAt){
           this.createdAt= new Date(object.createdAt);
       }
       if(object.updatedAt){
           this.updatedAt= new Date(object.updatedAt);
       }
       this.createBy= object.createBy;
       this.updateBy= object.updateBy;
       if(object.removeAt){
           this.removeAt= new Date(object.removeAt);
       }
       this.removeBy= object.removeBy;
       this.createdFromIp= object.createdFromIp;
       this.updatedFromIp= object.updatedFromIp;
       if(object.restoredAt){
           this.restoredAt= new Date(object.restoredAt);
       }
       this.restoredBy= object.restoredBy;
          entityManager.persist(this)
      }
      this.postConstruct()
  }


get contact() {
const data = entityManager.get(this.contactId,'contact')
if(data instanceof Contact){
   return data
}else if(data){
   return new Contact(data)
}else{
const relation = entityManager.getRelation("contact", "dealsIds",this.id )
if(relation instanceof Contact){
   return relation
}else if(relation){
   return new Contact(relation)
}
}

}


get step() {
const data = entityManager.get(this.stepId,'pipelinestep')
if(data instanceof PipelineStep){
   return data
}else if(data){
   return new PipelineStep(data)
}else{
const relation = entityManager.getRelation("pipelinestep", "dealsIds",this.id )
if(relation instanceof PipelineStep){
   return relation
}else if(relation){
   return new PipelineStep(relation)
}
}

}


get tags() {
const rawData = entityManager.get(this.tagsIds,'tag') ?? []
const formattedData: Tag[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Tag)){
occ = new Tag (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('tag', 'dealId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Tag) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Tag)) {
occ = new Tag(data);
occ = new Tag(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get participants() {
const rawData = entityManager.get(this.participantsIds,'contact') ?? []
const formattedData: Contact[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Contact)){
occ = new Contact (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('contact', 'dealId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Contact) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Contact)) {
occ = new Contact(data);
occ = new Contact(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get activities() {
const rawData = entityManager.get(this.activitiesIds,'activity') ?? []
const formattedData: Activity[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Activity)){
occ = new Activity (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('activity', 'dealId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Activity) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Activity)) {
occ = new Activity(data);
occ = new Activity(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get assets() {
const rawData = entityManager.get(this.assetsIds,'asset') ?? []
const formattedData: Asset[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Asset)){
occ = new Asset (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('asset', 'dealId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Asset) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Asset)) {
occ = new Asset(data);
occ = new Asset(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get notes() {
const rawData = entityManager.get(this.notesIds,'note') ?? []
const formattedData: Note[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Note)){
occ = new Note (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('note', 'dealId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Note) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Note)) {
occ = new Note(data);
occ = new Note(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get company() {
const data = entityManager.get(this.companyId,'company')
if(data instanceof Company){
   return data
}else if(data){
   return new Company(data)
}else{
const relation = entityManager.getRelation("company", "dealsIds",this.id )
if(relation instanceof Company){
   return relation
}else if(relation){
   return new Company(relation)
}
}

}

}
