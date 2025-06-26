/* eslint-disabled */
import Pipeline from "./Pipeline";
import Deal from "./Deal";
import PipelineStepExtend from "./extends/PipelineStepExtend";

export default class PipelineStep extends PipelineStepExtend {

public id!: number;
public name = '';
public description? = '';
public successProbability?: number;
public pipeline!: Pipeline;
public deals: Array<Deal> = [];
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
this.pipeline = (object.pipeline instanceof Pipeline) ? object.pipeline : object.pipeline ? new Pipeline(object.pipeline) : object.pipeline;
       if(object.deals){
           object.deals.forEach((occ: any)=>{
               const deal= occ instanceof Deal? occ :  new Deal(occ);
               this.deals.push(deal);
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
      }
      this.postConstruct()
  }

}
