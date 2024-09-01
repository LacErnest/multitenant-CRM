import { Component, OnDestroy, OnInit } from '@angular/core';
import { GlobalService } from '../../../../../../core/services/global.service';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter, skip } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { Subscription } from 'rxjs';
import {
  DatatableMenuConfig,
  DatatableMenuStyle,
} from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { DesignTemplate } from '../../interfaces/design-template';

@Component({
  selector: 'oz-finance-design-template-list',
  templateUrl: './design-template-list.component.html',
  styleUrls: ['./design-template-list.component.scss'],
})
export class DesignTemplateListComponent implements OnInit, OnDestroy {
  designTemplates: { data: any[]; count: number };
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    export: false,
    add: false,
    import: false,
    refresh: false,
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    clone: false,
    export: false,
    delete: false,
    style: DatatableMenuStyle.INLINE,
  });
  protected table = 'design_templates';
  private navigationSub: Subscription;
  private companySub: Subscription;

  constructor(
    private globalService: GlobalService,
    protected route: ActivatedRoute,
    protected tablePreferencesService: TablePreferencesService,
    private router: Router,
    private toastService: ToastrService,
    protected appStateService: AppStateService
  ) {
    //
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();

    const userRole = this.globalService.getUserRole();
    this.buttonConfig.add =
      this.buttonConfig.delete =
      this.buttonConfig.edit =
        userRole === 0 || userRole === 1;
  }

  ngOnDestroy() {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  getData(): void {
    //
  }

  private getResolvedData(): void {
    const { design_templates } = this.route.snapshot.data;
    this.designTemplates = {
      data: design_templates,
      count: design_templates.length,
    };
  }

  /**
   * When user switches to an other company
   */
  private initSubscriptions(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all' || value.role > 1) {
          this.router.navigate(['/']).then();
        } else {
          this.router
            .navigate([
              '/' + value.id + '/settings/email_management/design_templates',
            ])
            .then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  get designTemplateCreationUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/design_templates/create`;
  }

  get designTemplateEditionPartialUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/design_templates/`;
  }

  /**
   * To add new design template
   */
  addDesignTemplate(): void {
    this.router.navigate([this.designTemplateCreationUrl]);
  }

  /**
   * To edit selected design template
   * @param row
   */
  editDesignTemplate(designTemplate: DesignTemplate): void {
    this.router.navigate(
      [this.designTemplateEditionPartialUrl + designTemplate.id + '/edit'],
      {
        relativeTo: this.route,
      }
    );
  }
}
