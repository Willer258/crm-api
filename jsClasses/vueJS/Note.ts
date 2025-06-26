/* eslint-disabled */
import Deal from "./Deal";
import Activity from "./Activity";
import Company from "./Company";
import Contact from "./Contact";
import NoteExtend from "./extends/NoteExtend";

export default class Note extends NoteExtend {

public id!: number;
public content = '';
public deal!: Deal;
public activity!: Activity;
public company!: Company;
public contact!: Contact;
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
this.deal = (object.deal instanceof Deal) ? object.deal : object.deal ? new Deal(object.deal) : object.deal;
this.activity = (object.activity instanceof Activity) ? object.activity : object.activity ? new Activity(object.activity) : object.activity;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
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
