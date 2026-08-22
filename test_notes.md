# /settings/companies/{company}/update

## sites
'Sites' is designed to be a database of all possible address preventing the artifical bloating of teh database by re-using addresses
this means that home depot and postal address should be the same and stored within the sites table

## branding
branding is required for each tenant to be used on printable documents

# /settings/users/{user}/update

## tenants
can we convert this to a table with a modal form to add new tenants

## roles
can we convert this to a table with a modal form to add new roles

# /settings/configuration

## google address validation
any configuration settings can be managed here to allow for api keys to be changed

# /dashboard
unused, can be used for metrics later

# /transport/vehicles/create

## movement capacity
we need to able to track the vehicle capacity. eg 3.5t, 13.5t etc

# /stock/makes

## makes
this needs a method to add new models, either adapt the new make form to include models, or add a control inline with each make row within the ellipsis

# /stock/equipment/create

## model field
this needs an inline search, we can use flux pro features

# /crm/sites/create

## address code field
this should be a hidden field used to prevent address duplication and should be auto generated from the physical validated address.

## address validation
the plan is to validate against google to ensure the address is correct

## easy address input
an intuative address adding feature validated against google

# /crm/customers/create

## home address
need an ability to add a home address

# /operations/movements/create

## required elements

### movement details
- Tenant (table)
- Move Reference
- Type
- Customer (table)(can be a tenant, ie to move a customers machine from tenant a to tenant b)
- Advice Note (nullable)
- Job Number (nullable)

### address 1
- site address (table)
- contact name
- contact number
- instructions/easy to find
- start / earliest
- end / latest
- notes
> the type of address will change depending on the movement type


### address 2
- site address (table)
- contact name
- contact number
- instructions/easy to find
- start / earliest
- end / latest
- notes
> the type of address will change depending on the movement type

### driver & vehicle
- vehicle (table)
- driver (table)
- notes

### movement items

#### movement item
> can be multiple per movement or manual line to allow for non equipment items
- equipment (table)
    - stock number
    - serial number
    - description
    - direction (collection, delivery)

#### movement sub items (item accessories)
> can be multiple accessories per item
- accessorie (table)
    - type (trailer, remote, straps, remote batteries, keys, outrigger pads, custom)
    - description
    - serial (if applicable)
    - qty

# pwa
no option to download or install

# /operations/movements

## user preferences
ability to set a preference for a default view
- day
- week
- month

## ui/ux improvements
- kanban functionality is desireable