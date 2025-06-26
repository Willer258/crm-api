/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import Deal from "./Deal";
import AssetExtend from "./extends/AssetExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Asset extends AssetExtend {


public id = '';
public src = '';
public contactId = '';
public companyId = '';
public dealId = '';
public type = '';
public name = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.src= object.src;
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
   if(object.dealId){
       this.dealId = object.dealId
   }
   if(typeof object.deal === "string"){
       const occ = entityManager.get(object.deal, "Deal")
       if (occ && typeof occ === "object") {
           this.dealId = occ.id
       }else{
           this.dealId = object.deal
       }
   }else if(object.deal instanceof Deal){
       this.dealId = object.deal.id
       }else  if(object.deal && object.deal.id){
       this.dealId = object.deal.id
       const occ = new Deal(object.deal)
       }else  if(object.deal && entityManager.get(object.deal.id,"Deal") instanceof Deal){
       this.dealId = entityManager.get(object.deal.id,"Deal").id
       }
       this.type= object.type;
       this.name= object.name;
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
const relation = entityManager.getRelation("contact", "filesIds",this.id )
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
const relation = entityManager.getRelation("company", "filesIds",this.id )
if(relation instanceof Company){
   return relation
}else if(relation){
   return new Company(relation)
}
}

}


get deal() {
const data = entityManager.get(this.dealId,'deal')
if(data instanceof Deal){
   return data
}else if(data){
   return new Deal(data)
}else{
const relation = entityManager.getRelation("deal", "filesIds",this.id )
if(relation instanceof Deal){
   return relation
}else if(relation){
   return new Deal(relation)
}
}

}

}
