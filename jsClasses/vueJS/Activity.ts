/* eslint-disabled */
import Deal from "./Deal";
import Note from "./Note";
import Contact from "./Contact";
import Company from "./Company";
import ActivityExtend from "./extends/ActivityExtend";

export default class Activity extends ActivityExtend {

public id!: number;
public type = '';
public startDate?: Date;
public endDate?: Date;
public location = '';
public performed!: boolean;
public notify!: boolean;
public notifyDate?: Date;
public description? = '';
public deal!: Deal;
public notes: Array<Note> = [];
public managers= [];
public contact!: Contact;
public company!: Company;
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
this.deal = (object.deal instanceof Deal) ? object.deal : object.deal ? new Deal(object.deal) : object.deal;
       if(object.notes){
           object.notes.forEach((occ: any)=>{
               const note= occ instanceof Note? occ :  new Note(occ);
               this.notes.push(note);
           });
       }
       this.managers= object.managers;
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
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
      }
      this.postConstruct()
  }

}
