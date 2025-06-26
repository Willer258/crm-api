/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import PhoneNumberExtend from "./extends/PhoneNumberExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class PhoneNumber extends PhoneNumberExtend {


public contactId = '';
public companyId = '';
public id = '';
public number = '';
public type? = '';

  constructor (object?: any) {
      super(object)
      if(object){
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
       this.id= object.id;
       this.number= object.number;
       this.type= object.type;
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
const relation = entityManager.getRelation("contact", "phonesIds",this.id )
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
const relation = entityManager.getRelation("company", "phonesIds",this.id )
if(relation instanceof Company){
   return relation
}else if(relation){
   return new Company(relation)
}
}

}

}
