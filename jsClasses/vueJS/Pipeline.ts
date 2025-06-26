/* eslint-disabled */
import PipelineStep from "./PipelineStep";
import PipelineExtend from "./extends/PipelineExtend";

export default class Pipeline extends PipelineExtend {

public id!: number;
public name = '';
public description? = '';
public roles= [];
public pipelineSteps: Array<PipelineStep> = [];
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
       if(object.pipelineSteps){
           object.pipelineSteps.forEach((occ: any)=>{
               const pipelinestep= occ instanceof PipelineStep? occ :  new PipelineStep(occ);
               this.pipelineSteps.push(pipelinestep);
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
      }
      this.postConstruct()
  }

}
