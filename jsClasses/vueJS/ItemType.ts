/* eslint-disabled */
import Company from "./Company";
import Contact from "./Contact";
import PropertyModel from "./PropertyModel";
import ItemTypeExtend from "./extends/ItemTypeExtend";

export default class ItemType extends ItemTypeExtend {

public id!: number;
public code = '';
public description? = '';
public companies: Array<Company> = [];
public contacts: Array<Contact> = [];
public propertyModels: Array<PropertyModel> = [];

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.code= object.code;
       this.description= object.description;
       if(object.companies){
           object.companies.forEach((occ: any)=>{
               const company= occ instanceof Company? occ :  new Company(occ);
               this.companies.push(company);
           });
       }
       if(object.contacts){
           object.contacts.forEach((occ: any)=>{
               const contact= occ instanceof Contact? occ :  new Contact(occ);
               this.contacts.push(contact);
           });
       }
       if(object.propertyModels){
           object.propertyModels.forEach((occ: any)=>{
               const propertymodel= occ instanceof PropertyModel? occ :  new PropertyModel(occ);
               this.propertyModels.push(propertymodel);
           });
       }
      }
      this.postConstruct()
  }

}
