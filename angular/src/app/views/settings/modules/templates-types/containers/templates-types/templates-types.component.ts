import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import moment from 'moment';
import { ToastrService } from 'ngx-toastr';
import { Subject } from 'rxjs';
import { filter, finalize, skip, takeUntil } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { settingsRoutesRoles } from 'src/app/views/settings/settings-roles';
import { SettingsService } from 'src/app/views/settings/settings.service';
import {
  TemplateList,
  TemplateModel,
} from '../../../../../../shared/interfaces/template-model';
import { TemplateTypeModalComponent } from '../../template-type-modal/template-type-modal.component';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-templates-types',
  templateUrl: './templates-types.component.html',
  styleUrls: ['./templates-types.component.scss'],
})
export class TemplatesTypesComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('templateTypeModal')
  public templateTypeModal: TemplateTypeModalComponent;

  public templates: TemplateModel[] = [];
  public templateList: TemplateList;
  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: false,
    filters: false,
    export: false,
    delete: false,
    add: !this.isOwnerReadOnly(),
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    export: false,
    clone: false,
    delete: !this.isOwnerReadOnly(),
    edit: !this.isOwnerReadOnly(),
    viewTemplate: true,
  });
  public templateColumns = [
    { prop: 'name', name: 'name', type: 'string' },
    { prop: 'created_at', name: 'created at', type: 'date' },
    { prop: 'updated_at', name: 'updated at', type: 'date' },
  ];

  private onDestroy$: Subject<void> = new Subject<void>();

  constructor(
    protected tablePreferencesService: TablePreferencesService,
    protected route: ActivatedRoute,
    private globalService: GlobalService,
    private router: Router,
    private settingsService: SettingsService,
    private toastrService: ToastrService,
    protected appStateService: AppStateService
  ) {
    super(tablePreferencesService, route, appStateService);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public addTemplate(): void {
    this.templateTypeModal.openModal(undefined).subscribe(value => {
      if (value) {
        this.createTemplate(value);
      }
    });
  }

  public editTemplate(template: TemplateModel): void {
    this.templateTypeModal.openModal(template).subscribe(value => {
      if (value) {
        this.updateTemplate(value);
      }
    });
  }

  public onSelectedTemplateClicked(template: TemplateModel): void {
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/settings/templates/${template.id}/view`,
      ])
      .then();
  }

  public onDeleteTemplateClicked(template: TemplateModel): void {
    this.deleteTemplate(template);
  }

  protected getData(): void {
    this.isLoading = true;

    this.settingsService
      .getTemplatesTypes()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.templates = response;
        this.templateList = {
          data: this.templates,
          count: this.templates.length,
        };
        this.templateList.data.forEach(template => {
          template.is_edit_allowed = true;
          template.is_deletion_allowed = true;

          return template;
        });
      });
  }

  private getResolvedData(): void {
    this.getData();
  }

  private createTemplate(template: TemplateModel): void {
    this.isLoading = true;

    this.settingsService
      .addTemplate(template)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.toastrService.success(
            'Template category was successfully added',
            'Success'
          );
          this.getData();
        },
        error => {
          this.toastrService.error(
            'Template category was not created',
            'Error'
          );
        }
      );
  }

  private updateTemplate(template: TemplateModel): void {
    this.isLoading = true;

    this.settingsService
      .editTemplate(template.id, template)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.getData();
          this.toastrService.success(
            'Template category was successfully updated',
            'Success'
          );
        },
        error => {
          this.toastrService.error(
            'Template category was not updated',
            'Error'
          );
        }
      );
  }

  private deleteTemplate(template: TemplateModel): void {
    this.isLoading = true;
    this.settingsService
      .deleteTemplate(template[0].id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        () => {
          this.getData();
          this.toastrService.success(
            'Template category was successfully deleted',
            'Success'
          );
        },
        error => {
          this.toastrService.error(
            'Template category was not deleted',
            'Error'
          );
        }
      );
  }
  //#endregion

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1), takeUntil(this.onDestroy$))
      .subscribe(value => {
        const allowedRoles = settingsRoutesRoles.templates;

        if (value?.id === 'all' || !allowedRoles.includes(value.role)) {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate([`/${value.id}/settings/templates`]).then();
        }
      });

    this.router.events
      .pipe(
        filter(e => e instanceof NavigationEnd),
        takeUntil(this.onDestroy$)
      )
      .subscribe(() => {
        this.getResolvedData();
      });
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
