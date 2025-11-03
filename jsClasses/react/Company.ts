/* eslint-disabled */
import Property from "./Property";
import Contact from "./Contact";
import ItemType from "./ItemType";
import PhoneNumber from "./PhoneNumber";
import Asset from "./Asset";
import Mail from "./Mail";
import Note from "./Note";
import Tag from "./Tag";
import Activity from "./Activity";
import Deal from "./Deal";
import CompanyExtend from "./extends/CompanyExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Company extends CompanyExtend {


public id = '';
public propertiesIds: string[] = [];
public contactsIds: string[] = [];
public itemTypeId = '';
public phonesIds: string[] = [];
public assetsIds: string[] = [];
public mailsIds: string[] = [];
public notesIds: string[] = [];
public tagsIds: string[] = [];
public activitiesIds: string[] = [];
public photo? = '';
public dealsIds: string[] = [];
public manager? = '';
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
       if(object.contactsIds){
               this.contactsIds= object.contactsIds
       }
       if(object.contacts){
           object.contacts.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Contact")
       if (found && typeof found === "object") {
               this.contactsIds.push(found.id);
       }else{
               this.contactsIds.push(occ);
       }
   }else{
               let contact= occ instanceof Contact? occ :   new Contact(occ);
       if (contact && !(contact instanceof Contact)) {
            contact = new Contact(contact)
       }
               entityManager.persist(contact)
               this.contactsIds.push(contact.id);
       }
           });
       }
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
       this.photo= object.photo;
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
       this.manager= object.manager;
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
const relations = entityManager.getRelations('property', 'companyId');
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


get contacts() {
const rawData = entityManager.get(this.contactsIds,'contact') ?? []
const formattedData: Contact[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Contact)){
occ = new Contact (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('contact', 'companyId');
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


get itemType() {
const data = entityManager.get(this.itemTypeId,'itemtype')
if(data instanceof ItemType){
   return data
}else if(data){
   return new ItemType(data)
}else{
const relation = entityManager.getRelation("itemtype", "companiesIds",this.id )
if(relation instanceof ItemType){
   return relation
}else if(relation){
   return new ItemType(relation)
}
}

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
const relations = entityManager.getRelations('phonenumber', 'companyId');
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
const relations = entityManager.getRelations('asset', 'companyId');
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
const relations = entityManager.getRelations('mail', 'companyId');
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
const relations = entityManager.getRelations('note', 'companyId');
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
const relations = entityManager.getRelations('tag', 'companyId');
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
const relations = entityManager.getRelations('activity', 'companyId');
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
const relations = entityManager.getRelations('deal', 'companyId');
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

}
