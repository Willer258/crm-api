/* eslint-disabled */
import Deal from "./Deal";
import Note from "./Note";
import ActivityExtend from "./extends/ActivityExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Activity extends ActivityExtend {


public id = '';
public type = '';
public startDate?: Date;
public endDate?: Date;
public location = '';
public performed = '';
public notify = '';
public notifyDate?: Date;
public description? = '';
public dealId = '';
public notesIds: string[] = [];
public managers= [];
public name = '';
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
       this.type= object.type;
       if(object.startDate){
           this.startDate= new Date(object.startDate);
       }
       if(object.endDate){
           this.endDate= new Date(object.endDate);
       }
       this.location= object.location;
       this.performed= object.performed;
       this.notify= object.notify;
       if(object.notifyDate){
           this.notifyDate= new Date(object.notifyDate);
       }
       this.description= object.description;
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
       this.managers= object.managers;
       this.name= object.name;
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


get deal() {
const data = entityManager.get(this.dealId,'deal')
if(data instanceof Deal){
   return data
}else if(data){
   return new Deal(data)
}else{
const relation = entityManager.getRelation("deal", "activitiesIds",this.id )
if(relation instanceof Deal){
   return relation
}else if(relation){
   return new Deal(relation)
}
}

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
const relations = entityManager.getRelations('note', 'activityId');
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

}
