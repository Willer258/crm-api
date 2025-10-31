/* eslint-disabled */
import Property from "./Property";
import Deal from "./Deal";
import Company from "./Company";
import ItemType from "./ItemType";
import Asset from "./Asset";
import PhoneNumber from "./PhoneNumber";
import Mail from "./Mail";
import Note from "./Note";
import Tag from "./Tag";
import Activity from "./Activity";
import ContactExtend from "./extends/ContactExtend";

export default class Contact extends ContactExtend {

public id!: number;
public properties: Array<Property> = [];
public deals: Array<Deal> = [];
public source = '';
public company!: Company;
public manager? = '';
public itemType!: ItemType;
public dealsAsParticipant: Array<Deal> = [];
public assets: Array<Asset> = [];
public phones: Array<PhoneNumber> = [];
public mails: Array<Mail> = [];
public notes: Array<Note> = [];
public tags: Array<Tag> = [];
public photo? = '';
public activities: Array<Activity> = [];
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
       if(object.properties){
           object.properties.forEach((occ: any)=>{
               const property= occ instanceof Property? occ :  new Property(occ);
               this.properties.push(property);
           });
       }
       if(object.deals){
           object.deals.forEach((occ: any)=>{
               const deal= occ instanceof Deal? occ :  new Deal(occ);
               this.deals.push(deal);
           });
       }
       this.source= object.source;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
       this.manager= object.manager;
this.itemType = (object.itemType instanceof ItemType) ? object.itemType : object.itemType ? new ItemType(object.itemType) : object.itemType;
       if(object.dealsAsParticipant){
           object.dealsAsParticipant.forEach((occ: any)=>{
               const deal= occ instanceof Deal? occ :  new Deal(occ);
               this.dealsAsParticipant.push(deal);
           });
       }
       if(object.assets){
           object.assets.forEach((occ: any)=>{
               const asset= occ instanceof Asset? occ :  new Asset(occ);
               this.assets.push(asset);
           });
       }
       if(object.phones){
           object.phones.forEach((occ: any)=>{
               const phonenumber= occ instanceof PhoneNumber? occ :  new PhoneNumber(occ);
               this.phones.push(phonenumber);
           });
       }
       if(object.mails){
           object.mails.forEach((occ: any)=>{
               const mail= occ instanceof Mail? occ :  new Mail(occ);
               this.mails.push(mail);
           });
       }
       if(object.notes){
           object.notes.forEach((occ: any)=>{
               const note= occ instanceof Note? occ :  new Note(occ);
               this.notes.push(note);
           });
       }
       if(object.tags){
           object.tags.forEach((occ: any)=>{
               const tag= occ instanceof Tag? occ :  new Tag(occ);
               this.tags.push(tag);
           });
       }
       this.photo= object.photo;
       if(object.activities){
           object.activities.forEach((occ: any)=>{
               const activity= occ instanceof Activity? occ :  new Activity(occ);
               this.activities.push(activity);
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
       if(object.restoredAt){
           this.restoredAt= new Date(object.restoredAt);
       }
       this.restoredBy= object.restoredBy;
      }
      this.postConstruct()
  }

}
