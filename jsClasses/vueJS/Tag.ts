/* eslint-disabled */
import Deal from "./Deal";
import Contact from "./Contact";
import Company from "./Company";
import TagExtend from "./extends/TagExtend";

export default class Tag extends TagExtend {

public id!: number;
public label = '';
public code = '';
public description? = '';
public deals: Array<Deal> = [];
public color = '';
public contacts: Array<Contact> = [];
public companies: Array<Company> = [];

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.label= object.label;
       this.code= object.code;
       this.description= object.description;
       if(object.deals){
           object.deals.forEach((occ: any)=>{
               const deal= occ instanceof Deal? occ :  new Deal(occ);
               this.deals.push(deal);
           });
       }
       this.color= object.color;
       if(object.contacts){
           object.contacts.forEach((occ: any)=>{
               const contact= occ instanceof Contact? occ :  new Contact(occ);
               this.contacts.push(contact);
           });
       }
       if(object.companies){
           object.companies.forEach((occ: any)=>{
               const company= occ instanceof Company? occ :  new Company(occ);
               this.companies.push(company);
           });
       }
      }
      this.postConstruct()
  }

}
