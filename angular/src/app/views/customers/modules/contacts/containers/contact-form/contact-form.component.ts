import { Component, OnDestroy, OnInit } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { finalize, skip } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { transition, trigger, useAnimation } from '@angular/animations';
import { Subscription } from 'rxjs';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { atLeastOne } from 'src/app/core/classes/helpers';
import { emailRegEx } from 'src/app/shared/constants/regex';
import { GlobalService } from 'src/app/core/services/global.service';
import { Contact } from 'src/app/shared/interfaces/contact';
import { ContactsService } from 'src/app/views/customers/modules/contacts/contacts.service';
import {
  ContactGender,
  getContactGenderDescriptions,
} from 'src/app/views/customers/modules/contacts/enums/contact-gender.enum';
import { UserRole } from '../../../../../../shared/enums/user-role.enum';

@Component({
  selector: 'oz-finance-contact-form',
  templateUrl: './contact-form.component.html',
  styleUrls: ['./contact-form.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class ContactFormComponent implements OnInit, OnDestroy {
  public contactForm: FormGroup;
  public contact: Contact;
  public customerId: string;
  public isLoading = false;

  // TODO: remove if gender enum is fixed
  public genders = getContactGenderDescriptions();

  private companySub: Subscription;

  constructor(
    private contactsService: ContactsService,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private route: ActivatedRoute,
    private router: Router,
    private toastService: ToastrService
  ) {}

  public get contactHeadingContent(): string {
    const firstName = this.contactForm.controls.first_name?.value;
    const lastName = this.contactForm.controls.last_name?.value;

    if (this.contact) {
      if (firstName || lastName) {
        return `Edit ${firstName ?? ''} ${lastName ?? ''}`;
      } else {
        return 'Edit contact';
      }
    } else {
      return 'Create new contact';
    }
  }

  public ngOnInit(): void {
    this.customerId = this.route.snapshot.params.customer_id;
    this.getResolvedData();
    this.initContactForm();
    this.patchValueContactForm();
    this.subscribeToCompanyChange();
  }

  public ngOnDestroy(): void {
    this.companySub?.unsubscribe();
  }

  public submitForm(): void {
    if (this.contactForm.valid && !this.isLoading) {
      this.contact ? this.editContact() : this.createContact();
    }
  }

  public createContact(): void {
    this.isLoading = true;
    this.contactsService
      .createContact(this.customerId, this.contactForm.value)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.router
            .navigate([
              `/${this.globalService.currentCompany.id}/customers/${this.customerId}/contacts/${response.id}/edit`,
            ])
            .then();
          this.toastService.success(
            'Contact has been successfully created',
            'Success'
          );
        },
        error => {
          this.handleError(error);
          this.toastService.error('Contact has not been created', 'Error');
        }
      );
  }

  public editContact(): void {
    this.isLoading = true;
    this.contactsService
      .editContact(this.customerId, this.contact.id, this.contactForm.value)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          this.contact = response;
          this.patchValueContactForm();
          this.toastService.success(
            'Contact has been successfully updated',
            'Success'
          );
        },
        error => {
          this.handleError(error);
          this.toastService.error('Contact has not been updated', 'Error');
        }
      );
  }

  public handleError(error: any): void {
    if (error.errors.email) {
      const [email_error] = error.errors.email;
      this.contactForm.controls.email.setErrors({ error: email_error });
    }
  }

  private isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  private initContactForm(): void {
    this.contactForm = this.fb.group(
      {
        first_name: new FormControl(''),
        last_name: new FormControl(''),
        email: new FormControl('', [Validators.pattern(emailRegEx)]),
        department: new FormControl(''),
        title: new FormControl(''),
        phone_number: new FormControl(''),
        gender: new FormControl(ContactGender.MALE, Validators.required),
        linked_in_profile: new FormControl(
          undefined,
          Validators.pattern(
            /http(s)?:\/\/([\w]+\.)?linkedin\.com\/in\/[A-z0-9_-]+\/?/
          )
        ),
      },
      { validator: atLeastOne(Validators.required) }
    );

    if (this.isOwnerReadOnly()) {
      this.contactForm.disable();
    }
  }

  private getResolvedData(): void {
    this.contact = this.route.snapshot.data.contact;
  }

  private patchValueContactForm(): void {
    if (this.contact) {
      this.contactForm.patchValue(this.contact);
    }
  }

  private subscribeToCompanyChange(): void {
    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/contacts']).then();
        }
      });
  }
}
