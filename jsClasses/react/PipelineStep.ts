/* eslint-disabled */
import Pipeline from "./Pipeline";
import Deal from "./Deal";
import PipelineStepExtend from "./extends/PipelineStepExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class PipelineStep extends PipelineStepExtend {


public id = '';
public name = '';
public description? = '';
public successProbability? = '';
public pipelineId = '';
public dealsIds: string[] = [];
public color = '';
public ranking? = '';
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
       this.name= object.name;
       this.description= object.description;
       this.successProbability= object.successProbability;
   if(object.pipelineId){
       this.pipelineId = object.pipelineId
   }
   if(typeof object.pipeline === "string"){
       const occ = entityManager.get(object.pipeline, "Pipeline")
       if (occ && typeof occ === "object") {
           this.pipelineId = occ.id
       }else{
           this.pipelineId = object.pipeline
       }
   }else if(object.pipeline instanceof Pipeline){
       this.pipelineId = object.pipeline.id
       }else  if(object.pipeline && object.pipeline.id){
       this.pipelineId = object.pipeline.id
       const occ = new Pipeline(object.pipeline)
       }else  if(object.pipeline && entityManager.get(object.pipeline.id,"Pipeline") instanceof Pipeline){
       this.pipelineId = entityManager.get(object.pipeline.id,"Pipeline").id
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
       this.color= object.color;
       this.ranking= object.ranking;
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


get pipeline() {
const data = entityManager.get(this.pipelineId,'pipeline')
if(data instanceof Pipeline){
   return data
}else if(data){
   return new Pipeline(data)
}else{
const relation = entityManager.getRelation("pipeline", "pipelineStepsIds",this.id )
if(relation instanceof Pipeline){
   return relation
}else if(relation){
   return new Pipeline(relation)
}
}

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
const relations = entityManager.getRelations('deal', 'pipelinestepId');
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
