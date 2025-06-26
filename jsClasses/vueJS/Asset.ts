/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import Deal from "./Deal";
import AssetExtend from "./extends/AssetExtend";

export default class Asset extends AssetExtend {

public id!: number;
public src = '';
public contact!: Contact;
public company!: Company;
public deal!: Deal;
public type = '';
public name = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.src= object.src;
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
this.deal = (object.deal instanceof Deal) ? object.deal : object.deal ? new Deal(object.deal) : object.deal;
       this.type= object.type;
       this.name= object.name;
      }
      this.postConstruct()
  }

}
