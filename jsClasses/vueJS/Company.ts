/* eslint-disabled */
import Property from "./Property";
import Contact from "./Contact";
import ItemType from "./ItemType";
import PhoneNumber from "./PhoneNumber";
import Asset from "./Asset";
import Mail from "./Mail";
import Note from "./Note";
import Tag from "./Tag";
import Activity from "./Activity";
import Deal from "./Deal";
import CompanyExtend from "./extends/CompanyExtend";

export default class Company extends CompanyExtend {

public id!: number;
public properties: Array<Property> = [];
public contacts: Array<Contact> = [];
public itemType!: ItemType;
public phones: Array<PhoneNumber> = [];
public assets: Array<Asset> = [];
public mails: Array<Mail> = [];
public notes: Array<Note> = [];
public tags: Array<Tag> = [];
public activities: Array<Activity> = [];
public photo? = '';
public deals: Array<Deal> = [];
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
       if(object.contacts){
           object.contacts.forEach((occ: any)=>{
               const contact= occ instanceof Contact? occ :  new Contact(occ);
               this.contacts.push(contact);
           });
       }
this.itemType = (object.itemType instanceof ItemType) ? object.itemType : object.itemType ? new ItemType(object.itemType) : object.itemType;
       if(object.phones){
           object.phones.forEach((occ: any)=>{
               const phonenumber= occ instanceof PhoneNumber? occ :  new PhoneNumber(occ);
               this.phones.push(phonenumber);
           });
       }
       if(object.assets){
           object.assets.forEach((occ: any)=>{
               const asset= occ instanceof Asset? occ :  new Asset(occ);
               this.assets.push(asset);
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
       if(object.activities){
           object.activities.forEach((occ: any)=>{
               const activity= occ instanceof Activity? occ :  new Activity(occ);
               this.activities.push(activity);
           });
       }
       this.photo= object.photo;
       if(object.deals){
           object.deals.forEach((occ: any)=>{
               const deal= occ instanceof Deal? occ :  new Deal(occ);
               this.deals.push(deal);
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
