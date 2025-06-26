/* eslint-disabled */
import Deal from "./Deal";
import Activity from "./Activity";
import Company from "./Company";
import Contact from "./Contact";
import NoteExtend from "./extends/NoteExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Note extends NoteExtend {


public id = '';
public content = '';
public dealId = '';
public activityId = '';
public companyId = '';
public contactId = '';
public uuid? = '';
public createdAt?: Date;
public updatedAt?: Date;
public createBy? = '';
public updateBy? = '';
public removeAt?: Date;
public removeBy? = '';
public createdFromIp? = '';
public updatedFromIp? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.content= object.content;
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
   if(object.activityId){
       this.activityId = object.activityId
   }
   if(typeof object.activity === "string"){
       const occ = entityManager.get(object.activity, "Activity")
       if (occ && typeof occ === "object") {
           this.activityId = occ.id
       }else{
           this.activityId = object.activity
       }
   }else if(object.activity instanceof Activity){
       this.activityId = object.activity.id
       }else  if(object.activity && object.activity.id){
       this.activityId = object.activity.id
       const occ = new Activity(object.activity)
       }else  if(object.activity && entityManager.get(object.activity.id,"Activity") instanceof Activity){
       this.activityId = entityManager.get(object.activity.id,"Activity").id
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
          entityManager.persist(this)
      }
      this.postConstruct()
  }


get deal() {
const data = entityManager.get(this.dealId,'deal')
if(data instanceof Deal){
   return data
}else if(data){
   return new Deal(data)
}else{
const relation = entityManager.getRelation("deal", "notesIds",this.id )
if(relation instanceof Deal){
   return relation
}else if(relation){
   return new Deal(relation)
}
}

}


get activity() {
const data = entityManager.get(this.activityId,'activity')
if(data instanceof Activity){
   return data
}else if(data){
   return new Activity(data)
}else{
const relation = entityManager.getRelation("activity", "notesIds",this.id )
if(relation instanceof Activity){
   return relation
}else if(relation){
   return new Activity(relation)
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
const relation = entityManager.getRelation("company", "notesIds",this.id )
if(relation instanceof Company){
   return relation
}else if(relation){
   return new Company(relation)
}
}

}


get contact() {
const data = entityManager.get(this.contactId,'contact')
if(data instanceof Contact){
   return data
}else if(data){
   return new Contact(data)
}else{
const relation = entityManager.getRelation("contact", "notesIds",this.id )
if(relation instanceof Contact){
   return relation
}else if(relation){
   return new Contact(relation)
}
}

}

}
