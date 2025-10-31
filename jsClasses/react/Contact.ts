/* eslint-disabled */
import Property from "./Property";
import Deal from "./Deal";
import Company from "./Company";
import ItemType from "./ItemType";
import Asset from "./Asset";
import PhoneNumber from "./PhoneNumber";
import Mail from "./Mail";
import Note from "./Note";
import Tag from "./Tag";
import Activity from "./Activity";
import ContactExtend from "./extends/ContactExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Contact extends ContactExtend {


public id = '';
public propertiesIds: string[] = [];
public dealsIds: string[] = [];
public source = '';
public companyId = '';
public manager? = '';
public itemTypeId = '';
public dealsAsParticipantIds: string[] = [];
public assetsIds: string[] = [];
public phonesIds: string[] = [];
public mailsIds: string[] = [];
public notesIds: string[] = [];
public tagsIds: string[] = [];
public photo? = '';
public activitiesIds: string[] = [];
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
       if(object.propertiesIds){
               this.propertiesIds= object.propertiesIds
       }
       if(object.properties){
           object.properties.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Property")
       if (found && typeof found === "object") {
               this.propertiesIds.push(found.id);
       }else{
               this.propertiesIds.push(occ);
       }
   }else{
               let property= occ instanceof Property? occ :   new Property(occ);
       if (property && !(property instanceof Property)) {
            property = new Property(property)
       }
               entityManager.persist(property)
               this.propertiesIds.push(property.id);
       }
           });
       }
       if(object.dealsIds){
               this.dealsIds= object.dealsIds
       }
       if(object.deals){
           object.deals.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Deal")
       if (found && typeof found === "object") {
               this.dealsIds.push(found.id);
       }else{
               this.dealsIds.push(occ);
       }
   }else{
               let deal= occ instanceof Deal? occ :   new Deal(occ);
       if (deal && !(deal instanceof Deal)) {
            deal = new Deal(deal)
       }
               entityManager.persist(deal)
               this.dealsIds.push(deal.id);
       }
           });
       }
       this.source= object.source;
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
       this.manager= object.manager;
   if(object.itemTypeId){
       this.itemTypeId = object.itemTypeId
   }
   if(typeof object.itemType === "string"){
       const occ = entityManager.get(object.itemType, "ItemType")
       if (occ && typeof occ === "object") {
           this.itemTypeId = occ.id
       }else{
           this.itemTypeId = object.itemType
       }
   }else if(object.itemType instanceof ItemType){
       this.itemTypeId = object.itemType.id
       }else  if(object.itemType && object.itemType.id){
       this.itemTypeId = object.itemType.id
       const occ = new ItemType(object.itemType)
       }else  if(object.itemType && entityManager.get(object.itemType.id,"ItemType") instanceof ItemType){
       this.itemTypeId = entityManager.get(object.itemType.id,"ItemType").id
       }
       if(object.dealsAsParticipantIds){
               this.dealsAsParticipantIds= object.dealsAsParticipantIds
       }
       if(object.dealsAsParticipant){
           object.dealsAsParticipant.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Deal")
       if (found && typeof found === "object") {
               this.dealsAsParticipantIds.push(found.id);
       }else{
               this.dealsAsParticipantIds.push(occ);
       }
   }else{
               let deal= occ instanceof Deal? occ :   new Deal(occ);
       if (deal && !(deal instanceof Deal)) {
            deal = new Deal(deal)
       }
               entityManager.persist(deal)
               this.dealsAsParticipantIds.push(deal.id);
       }
           });
       }
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
       if(object.phonesIds){
               this.phonesIds= object.phonesIds
       }
       if(object.phones){
           object.phones.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "PhoneNumber")
       if (found && typeof found === "object") {
               this.phonesIds.push(found.id);
       }else{
               this.phonesIds.push(occ);
       }
   }else{
               let phonenumber= occ instanceof PhoneNumber? occ :   new PhoneNumber(occ);
       if (phonenumber && !(phonenumber instanceof PhoneNumber)) {
            phonenumber = new PhoneNumber(phonenumber)
       }
               entityManager.persist(phonenumber)
               this.phonesIds.push(phonenumber.id);
       }
           });
       }
       if(object.mailsIds){
               this.mailsIds= object.mailsIds
       }
       if(object.mails){
           object.mails.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Mail")
       if (found && typeof found === "object") {
               this.mailsIds.push(found.id);
       }else{
               this.mailsIds.push(occ);
       }
   }else{
               let mail= occ instanceof Mail? occ :   new Mail(occ);
       if (mail && !(mail instanceof Mail)) {
            mail = new Mail(mail)
       }
               entityManager.persist(mail)
               this.mailsIds.push(mail.id);
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
       this.photo= object.photo;
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


get properties() {
const rawData = entityManager.get(this.propertiesIds,'property') ?? []
const formattedData: Property[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Property)){
occ = new Property (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('property', 'contactId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Property) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Property)) {
occ = new Property(data);
occ = new Property(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get deals() {
const rawData = entityManager.get(this.dealsIds,'deal') ?? []
const formattedData: Deal[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Deal)){
occ = new Deal (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('deal', 'contactId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Deal) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Deal)) {
occ = new Deal(data);
occ = new Deal(data);
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
const relation = entityManager.getRelation("company", "contactsIds",this.id )
if(relation instanceof Company){
   return relation
}else if(relation){
   return new Company(relation)
}
}

}


get itemType() {
const data = entityManager.get(this.itemTypeId,'itemtype')
if(data instanceof ItemType){
   return data
}else if(data){
   return new ItemType(data)
}else{
const relation = entityManager.getRelation("itemtype", "contactsIds",this.id )
if(relation instanceof ItemType){
   return relation
}else if(relation){
   return new ItemType(relation)
}
}

}


get dealsAsParticipant() {
const rawData = entityManager.get(this.dealsAsParticipantIds,'deal') ?? []
const formattedData: Deal[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Deal)){
occ = new Deal (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('deal', 'contactId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Deal) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Deal)) {
occ = new Deal(data);
occ = new Deal(data);
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
const relations = entityManager.getRelations('asset', 'contactId');
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


get phones() {
const rawData = entityManager.get(this.phonesIds,'phonenumber') ?? []
const formattedData: PhoneNumber[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof PhoneNumber)){
occ = new PhoneNumber (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('phonenumber', 'contactId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: PhoneNumber) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof PhoneNumber)) {
occ = new PhoneNumber(data);
occ = new PhoneNumber(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get mails() {
const rawData = entityManager.get(this.mailsIds,'mail') ?? []
const formattedData: Mail[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Mail)){
occ = new Mail (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('mail', 'contactId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Mail) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Mail)) {
occ = new Mail(data);
occ = new Mail(data);
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
const relations = entityManager.getRelations('note', 'contactId');
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
const relations = entityManager.getRelations('tag', 'contactId');
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
const relations = entityManager.getRelations('activity', 'contactId');
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

}
