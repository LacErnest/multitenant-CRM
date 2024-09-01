export class DatatableDetailConfig {
  public showDetailActions = true;

  public constructor(config?: Partial<DatatableDetailConfig>) {
    Object.assign(this, config);
  }
}
