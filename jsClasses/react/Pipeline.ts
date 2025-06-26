/* eslint-disabled */
import PipelineStep from "./PipelineStep";
import PipelineExtend from "./extends/PipelineExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Pipeline extends PipelineExtend {


public id = '';
public name = '';
public description? = '';
public roles? = '';
public pipelineStepsIds: string[] = [];
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
       this.roles= object.roles;
       if(object.pipelineStepsIds){
               this.pipelineStepsIds= object.pipelineStepsIds
       }
       if(object.pipelineSteps){
           object.pipelineSteps.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "PipelineStep")
       if (found && typeof found === "object") {
               this.pipelineStepsIds.push(found.id);
       }else{
               this.pipelineStepsIds.push(occ);
       }
   }else{
               let pipelinestep= occ instanceof PipelineStep? occ :   new PipelineStep(occ);
       if (pipelinestep && !(pipelinestep instanceof PipelineStep)) {
            pipelinestep = new PipelineStep(pipelinestep)
       }
               entityManager.persist(pipelinestep)
               this.pipelineStepsIds.push(pipelinestep.id);
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
          entityManager.persist(this)
      }
      this.postConstruct()
  }


get pipelineSteps() {
const rawData = entityManager.get(this.pipelineStepsIds,'pipelinestep') ?? []
const formattedData: PipelineStep[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof PipelineStep)){
occ = new PipelineStep (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('pipelinestep', 'pipelineId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: PipelineStep) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof PipelineStep)) {
occ = new PipelineStep(data);
occ = new PipelineStep(data);
}
formattedData.push(occ);
}
});
return formattedData
}

}
