export enum DatatableMenuStyle {
  DROPDOWN,
  INLINE,
}
export class DatatableMenuConfig {
  public showMenu = true;
  public edit = true;
  public clone = true;
  public delete = true;
  public export = true;
  public invoice = false;
  public order = false;
  public view = false;
  public cancel = false;
  public uploadInvoice = false;
  public markAsDefault = false;
  public markAsLocal = false;
  public employeeAddHours = false;
  public addCommission = false;
  public viewTemplate = false;
  public style: DatatableMenuStyle = DatatableMenuStyle.DROPDOWN;

  public constructor(config?: Partial<DatatableMenuConfig>) {
    Object.assign(this, config);
  }
}
