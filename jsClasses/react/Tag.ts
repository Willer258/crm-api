/* eslint-disabled */
import Deal from "./Deal";
import Contact from "./Contact";
import Company from "./Company";
import TagExtend from "./extends/TagExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Tag extends TagExtend {


public id = '';
public label = '';
public code = '';
public description? = '';
public dealsIds: string[] = [];
public color = '';
public contactsIds: string[] = [];
public companiesIds: string[] = [];

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.label= object.label;
       this.code= object.code;
       this.description= object.description;
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
       this.color= object.color;
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
       if(object.companiesIds){
               this.companiesIds= object.companiesIds
       }
       if(object.companies){
           object.companies.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Company")
       if (found && typeof found === "object") {
               this.companiesIds.push(found.id);
       }else{
               this.companiesIds.push(occ);
       }
   }else{
               let company= occ instanceof Company? occ :   new Company(occ);
       if (company && !(company instanceof Company)) {
            company = new Company(company)
       }
               entityManager.persist(company)
               this.companiesIds.push(company.id);
       }
           });
       }
          entityManager.persist(this)
      }
      this.postConstruct()
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
const relations = entityManager.getRelations('deal', 'tagId');
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
const relations = entityManager.getRelations('contact', 'tagId');
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


get companies() {
const rawData = entityManager.get(this.companiesIds,'company') ?? []
const formattedData: Company[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Company)){
occ = new Company (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('company', 'tagId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Company) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Company)) {
occ = new Company(data);
occ = new Company(data);
}
formattedData.push(occ);
}
});
return formattedData
}

}
