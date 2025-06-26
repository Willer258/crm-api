/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import PropertyModel from "./PropertyModel";
import PropertyExtend from "./extends/PropertyExtend";

export default class Property extends PropertyExtend {

public id!: number;
public value = '';
public contact!: Contact;
public company!: Company;
public propertyModel!: PropertyModel;

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.value= object.value;
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
this.propertyModel = (object.propertyModel instanceof PropertyModel) ? object.propertyModel : object.propertyModel ? new PropertyModel(object.propertyModel) : object.propertyModel;
      }
      this.postConstruct()
  }

}
