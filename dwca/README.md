Publishing the data to GBIF
===========================



## Step 1 Create dataset on GBIF

Create a dataset on GBIF using registry API. The **publishingOrganizationKey** is the publisher UUID that you see in the link to the publisher page: http://www.gbif.org/publisher/92f51af1-e917-49bc-a8ed-014ed3a77bec. You also need a **installationKey** provided by GBIF, and you also need to authenticate the call using your GBIF portal username and password.

http://api.gbif.org/v1/dataset

POST

```javascript
{
	"publishingOrganizationKey":"92f51af1-e917-49bc-a8ed-014ed3a77bec",
	"installationKey":"**<your key here>**",
	"title":"The Plant List with literature",
	"type":"CHECKLIST" 
}
```
RESPONSE

```javascript
“d9a4eedb-e985-4456-ad46-3df8472e00e8”
```

We now have a UUID (d9a4eedb-e985-4456-ad46-3df8472e00e8) for the dataset, which lives here: http://www.gbif.org/dataset/d9a4eedb-e985-4456-ad46-3df8472e00e8

## Step 2 Create and validate Darwin Core archive

Now we need to create the Darwin Core archive. 
I then generated a meta.xml file, and finally the Darwin Core Archive (DwC-A) (which is simply a zip file):

```
zip dwca.zip eml.xml meta.xml taxa.txt references.txt
```

Next we need to check that the DwC-A file is valid using the [Darwin Core Archive Validator](http://tools.gbif.org/dwca-validator/).

## Step 3 Create endpoint

Now we need to tell GBIF where to get the data. In this example, the Darwin Core Archive file is hosted by Github (make sure you link to the raw file).

http://api.gbif.org/v1/dataset/d9a4eedb-e985-4456-ad46-3df8472e00e8/endpoint

POST
```javascript
{
  “type”:”DWC_ARCHIVE”,
  “url”:”https://dl.dropboxusercontent.com/u/639486/dwca.zip”
}
```

RESPONSE 

HTTP 201 Created

```javascript
99444
```

## Step 4

Wait for GBIF to index the data…




