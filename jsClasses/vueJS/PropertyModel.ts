/* eslint-disabled */
import Property from "./Property";
import ItemType from "./ItemType";
import PropertyModelExtend from "./extends/PropertyModelExtend";

export default class PropertyModel extends PropertyModelExtend {

public id!: number;
public label = '';
public identifier!: boolean;
public type = '';
public properties: Array<Property> = [];
public itemType!: ItemType;
public class? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.label= object.label;
       this.identifier= object.identifier;
       this.type= object.type;
       if(object.properties){
           object.properties.forEach((occ: any)=>{
               const property= occ instanceof Property? occ :  new Property(occ);
               this.properties.push(property);
           });
       }
this.itemType = (object.itemType instanceof ItemType) ? object.itemType : object.itemType ? new ItemType(object.itemType) : object.itemType;
       this.class= object.class;
      }
      this.postConstruct()
  }

}
