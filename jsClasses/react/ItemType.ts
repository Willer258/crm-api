/* eslint-disabled */
import Company from "./Company";
import Contact from "./Contact";
import PropertyModel from "./PropertyModel";
import ItemTypeExtend from "./extends/ItemTypeExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class ItemType extends ItemTypeExtend {


public id = '';
public code = '';
public description? = '';
public companiesIds: string[] = [];
public contactsIds: string[] = [];
public propertyModelsIds: string[] = [];

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.code= object.code;
       this.description= object.description;
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
       if(object.propertyModelsIds){
               this.propertyModelsIds= object.propertyModelsIds
       }
       if(object.propertyModels){
           object.propertyModels.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "PropertyModel")
       if (found && typeof found === "object") {
               this.propertyModelsIds.push(found.id);
       }else{
               this.propertyModelsIds.push(occ);
       }
   }else{
               let propertymodel= occ instanceof PropertyModel? occ :   new PropertyModel(occ);
       if (propertymodel && !(propertymodel instanceof PropertyModel)) {
            propertymodel = new PropertyModel(propertymodel)
       }
               entityManager.persist(propertymodel)
               this.propertyModelsIds.push(propertymodel.id);
       }
           });
       }
          entityManager.persist(this)
      }
      this.postConstruct()
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
const relations = entityManager.getRelations('company', 'itemtypeId');
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
const relations = entityManager.getRelations('contact', 'itemtypeId');
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


get propertyModels() {
const rawData = entityManager.get(this.propertyModelsIds,'propertymodel') ?? []
const formattedData: PropertyModel[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof PropertyModel)){
occ = new PropertyModel (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('propertymodel', 'itemtypeId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: PropertyModel) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof PropertyModel)) {
occ = new PropertyModel(data);
occ = new PropertyModel(data);
}
formattedData.push(occ);
}
});
return formattedData
}

}
