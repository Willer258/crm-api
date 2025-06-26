/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import PropertyModel from "./PropertyModel";
import PropertyExtend from "./extends/PropertyExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Property extends PropertyExtend {


public id = '';
public value = '';
public contactId = '';
public companyId = '';
public propertyModelId = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.value= object.value;
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
   if(object.propertyModelId){
       this.propertyModelId = object.propertyModelId
   }
   if(typeof object.propertyModel === "string"){
       const occ = entityManager.get(object.propertyModel, "PropertyModel")
       if (occ && typeof occ === "object") {
           this.propertyModelId = occ.id
       }else{
           this.propertyModelId = object.propertyModel
       }
   }else if(object.propertyModel instanceof PropertyModel){
       this.propertyModelId = object.propertyModel.id
       }else  if(object.propertyModel && object.propertyModel.id){
       this.propertyModelId = object.propertyModel.id
       const occ = new PropertyModel(object.propertyModel)
       }else  if(object.propertyModel && entityManager.get(object.propertyModel.id,"PropertyModel") instanceof PropertyModel){
       this.propertyModelId = entityManager.get(object.propertyModel.id,"PropertyModel").id
       }
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
const relation = entityManager.getRelation("contact", "propertiesIds",this.id )
if(relation instanceof Contact){
   return relation
}else if(relation){
   return new Contact(relation)
}
}

}


get company() {
const data = entityManager.get(this.companyId,'company')
if(data instanceof Company){
   return data
}else if(data){
   return new Company(data)
}else{
const relation = entityManager.getRelation("company", "propertiesIds",this.id )
if(relation instanceof Company){
   return relation
}else if(relation){
   return new Company(relation)
}
}

}


get propertyModel() {
const data = entityManager.get(this.propertyModelId,'propertymodel')
if(data instanceof PropertyModel){
   return data
}else if(data){
   return new PropertyModel(data)
}else{
const relation = entityManager.getRelation("propertymodel", "propertiesIds",this.id )
if(relation instanceof PropertyModel){
   return relation
}else if(relation){
   return new PropertyModel(relation)
}
}

}

}
