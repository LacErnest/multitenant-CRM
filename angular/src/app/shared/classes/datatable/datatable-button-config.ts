export class DatatableButtonConfig {
  constructor(config?: Partial<DatatableButtonConfig>) {
    Object.assign(this, config);
  }

  columns = true;
  filters = true;
  add = true;
  delete = true;
  export = true;
  edit = true;
  import = false;
  refresh = false;
}
