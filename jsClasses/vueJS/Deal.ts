/* eslint-disabled */
import Contact from "./Contact";
import PipelineStep from "./PipelineStep";
import Tag from "./Tag";
import Activity from "./Activity";
import Asset from "./Asset";
import Note from "./Note";
import Company from "./Company";
import DealExtend from "./extends/DealExtend";

export default class Deal extends DealExtend {

public id!: number;
public contact!: Contact;
public object = '';
public manager = '';
public step!: PipelineStep;
public tags: Array<Tag> = [];
public participants: Array<Contact> = [];
public activities: Array<Activity> = [];
public products= [];
public assets: Array<Asset> = [];
public notes: Array<Note> = [];
public company!: Company;
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
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
       this.object= object.object;
       this.manager= object.manager;
this.step = (object.step instanceof PipelineStep) ? object.step : object.step ? new PipelineStep(object.step) : object.step;
       if(object.tags){
           object.tags.forEach((occ: any)=>{
               const tag= occ instanceof Tag? occ :  new Tag(occ);
               this.tags.push(tag);
           });
       }
       if(object.participants){
           object.participants.forEach((occ: any)=>{
               const contact= occ instanceof Contact? occ :  new Contact(occ);
               this.participants.push(contact);
           });
       }
       if(object.activities){
           object.activities.forEach((occ: any)=>{
               const activity= occ instanceof Activity? occ :  new Activity(occ);
               this.activities.push(activity);
           });
       }
       this.products= object.products;
       if(object.assets){
           object.assets.forEach((occ: any)=>{
               const asset= occ instanceof Asset? occ :  new Asset(occ);
               this.assets.push(asset);
           });
       }
       if(object.notes){
           object.notes.forEach((occ: any)=>{
               const note= occ instanceof Note? occ :  new Note(occ);
               this.notes.push(note);
           });
       }
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
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
