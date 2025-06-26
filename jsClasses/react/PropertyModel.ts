/* eslint-disabled */
import Property from "./Property";
import ItemType from "./ItemType";
import PropertyModelExtend from "./extends/PropertyModelExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class PropertyModel extends PropertyModelExtend {


public id = '';
public label = '';
public identifier = '';
public type = '';
public propertiesIds: string[] = [];
public itemTypeId = '';
public class? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.label= object.label;
       this.identifier= object.identifier;
       this.type= object.type;
       if(object.propertiesIds){
               this.propertiesIds= object.propertiesIds
       }
       if(object.properties){
           object.properties.forEach((occ: any)=>{
   if(typeof occ === "string"){
       const found = entityManager.get(occ, "Property")
       if (found && typeof found === "object") {
               this.propertiesIds.push(found.id);
       }else{
               this.propertiesIds.push(occ);
       }
   }else{
               let property= occ instanceof Property? occ :   new Property(occ);
       if (property && !(property instanceof Property)) {
            property = new Property(property)
       }
               entityManager.persist(property)
               this.propertiesIds.push(property.id);
       }
           });
       }
   if(object.itemTypeId){
       this.itemTypeId = object.itemTypeId
   }
   if(typeof object.itemType === "string"){
       const occ = entityManager.get(object.itemType, "ItemType")
       if (occ && typeof occ === "object") {
           this.itemTypeId = occ.id
       }else{
           this.itemTypeId = object.itemType
       }
   }else if(object.itemType instanceof ItemType){
       this.itemTypeId = object.itemType.id
       }else  if(object.itemType && object.itemType.id){
       this.itemTypeId = object.itemType.id
       const occ = new ItemType(object.itemType)
       }else  if(object.itemType && entityManager.get(object.itemType.id,"ItemType") instanceof ItemType){
       this.itemTypeId = entityManager.get(object.itemType.id,"ItemType").id
       }
       this.class= object.class;
          entityManager.persist(this)
      }
      this.postConstruct()
  }


get properties() {
const rawData = entityManager.get(this.propertiesIds,'property') ?? []
const formattedData: Property[] = []
rawData.forEach((data:any)=>{
let occ = data
if(!(data instanceof Property)){
occ = new Property (data)
}
   formattedData.push(occ)
})
const relations = entityManager.getRelations('property', 'propertymodelId');
relations.forEach((data: any) => {
let occ = data;
const exist = formattedData.find((s: Property) => {
return s.id === occ.id;
});
if (!exist) {
if (!(data instanceof Property)) {
occ = new Property(data);
occ = new Property(data);
}
formattedData.push(occ);
}
});
return formattedData
}


get itemType() {
const data = entityManager.get(this.itemTypeId,'itemtype')
if(data instanceof ItemType){
   return data
}else if(data){
   return new ItemType(data)
}else{
const relation = entityManager.getRelation("itemtype", "propertyModelsIds",this.id )
if(relation instanceof ItemType){
   return relation
}else if(relation){
   return new ItemType(relation)
}
}

}

}
