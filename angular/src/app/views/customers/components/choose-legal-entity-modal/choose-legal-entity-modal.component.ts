import {
  Component,
  EventEmitter,
  OnInit,
  Output,
  TemplateRef,
  ViewChild,
} from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { GlobalService } from 'src/app/core/services/global.service';
import { ModalComponent } from 'src/app/shared/components/modal/modal.component';
import {
  CompanyLegalEntity,
  LegalEntity,
} from 'src/app/shared/interfaces/legal-entity';

@Component({
  selector: 'oz-finance-choose-legal-entity-modal',
  templateUrl: './choose-legal-entity-modal.component.html',
  styleUrls: ['./choose-legal-entity-modal.component.scss'],
})
export class ChooseLegalEntityModalComponent implements OnInit {
  @Output() public companyLegalEntityChosen: EventEmitter<string> =
    new EventEmitter<string>();

  @ViewChild('modal') public modal: ModalComponent;
  @ViewChild('chooseCompanyLegalEntityForm')
  public chooseCompanyLegalEntityForm: TemplateRef<any>;

  public companyLegalEntities: CompanyLegalEntity[];
  public legalEntityForm: FormGroup;

  public constructor(
    private fb: FormBuilder,
    private globalService: GlobalService
  ) {}

  public get isLegalEntityFormDisabled(): boolean {
    return this.legalEntityForm.invalid || !this.legalEntityForm.dirty;
  }

  public ngOnInit(): void {
    this.initLegalEntityForm();
    this.companyLegalEntities = this.globalService.currentLegalEntities;
  }

  public openCompanyLegalEntityModal(): void {
    this.modal.openModal().subscribe(
      () => this.addLegalEntityToCompany(),
      () => {},
      () => this.legalEntityForm.reset()
    );
  }

  private addLegalEntityToCompany(): void {
    this.companyLegalEntityChosen.emit(
      this.legalEntityForm.get('legal_entity_id').value
    );
  }

  private initLegalEntityForm(): void {
    this.legalEntityForm = this.fb.group({
      legal_entity_id: new FormControl(undefined, Validators.required),
    });
  }
}
