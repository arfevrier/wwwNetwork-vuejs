<template>
  <v-app>
    <v-app-bar
      app
      :absolute="absoluteAppbar"
      color="primary"
      dark
      :density="sizeAppbar"
    >
      <v-container class="py-0 fill-height">
        <v-icon
          class="mr-10"
          size="x-large"
        >
          mdi-web
        </v-icon>

        <v-btn
          text
          href="https://apps.arfevrier.fr/www_network/"
        >
        {{ title }}
        </v-btn>

        <v-spacer></v-spacer>
        <v-spacer></v-spacer>

        <div class="mr-2 text-subtitle-2">DNS Server:</div>

        <v-btn-toggle
          class="mr-6"
          v-model="dns"  
          variant="outlined"
        >
          <v-btn value="8.8.8.8">
            <span>8<sup>4</sup></span>
          </v-btn>
          <v-btn value="213.186.33.99">
            <span>OVH</span>
          </v-btn>
      </v-btn-toggle>

        <v-text-field
          :loading="chargement"
          hide-details
          density="default"
          label="IP/Domain/AS"
          append-inner-icon="mdi-magnify"
          v-model="url"
          @keydown.enter="generate"
          @click:append-inner="generate"
        ></v-text-field>
      </v-container>
    </v-app-bar>

    <v-main>
      <v-container class="d-flex">
        <v-alert 
          v-model="erreur" 
          type="warning" 
          color="primary" 
          closable 
          class="mx-auto my-4 text-center" 
          style="max-width: 300px">
          IP/Domain/AS Incorrecte
        </v-alert>
      </v-container>

      <v-container>
          <v-row>
            <div v-for="(object, index) in infos" :key="'I'+index" :class="`v-col-lg-${object.size}`">
              <InfoCard :title="object.title" :text="object.text" :size="object.size" :color="object.color" :loading="object.loading"/>
            </div>
          </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
  import InfoCard from './InfoCard';
  import functions from '../plugins/functions';
  import stc from '../plugins/static';

  export default {
    components: {
        InfoCard,
    },
    computed: {
      sizeAppbar() {
        return this.$vuetify.display.mobile ? 'prominent' : 'default'
      },
      title() {
        return this.$vuetify.display.mobile ? 'Network Info' : 'Whois, Nmap, Subdomain gathering'
      },
      absoluteAppbar() {
        return this.$vuetify.display.mobile ? true : false
      }
    },
    data: () => ({
      chargement: false,
      dns: '8.8.8.8',
      url: '',
      stc: stc,
      erreur: false,
      infos: [],
    }),
    mounted() {
      this.startup()
    },
    methods: {
        startup(){
            if (window.location.search.includes("?q=")){
              this.$data.url = decodeURIComponent(window.location.search.split("=")[1])
              this.generate()
            }
        },
        search(id){
          this.$data.url = id
          this.generate()
        },
        generate(){
            this.$data.erreur = false
            this.$data.chargement = true
            var info = this.$data.url
            this.$data.url = ''
            this.$data.infos.length = 0

            // AS
            if (info.startsWith('AS')){ 
              this.whois(info, 'AS', '#416ea5')
              this.$data.chargement = false

            // IPv4
            } else if (functions.isCharNumber(info.slice(-1))){
              this.nmap(info)
              this.whois(info, 'IP', '#416ea5')
              fetch(`${stc.afr.api}/host/ipv4/${info}`)
                .then(resp => {
                  if(!resp.ok){ throw 'http.code != 200' }
                  return resp.json()        
                })
                .then(resp => {
                    this.$data.infos.unshift({title: 'Info', text:`IP/AS Tested: ${info} & IP Reverse: ${resp.hostname}`, color:'#a54141', size:'6'})
                    if(resp.hostname != null && resp.hostname != false){
                      this.subdomains(resp.hostname);
                      this.whois(resp.hostname, 'Domain', '#41a57b');
                      this.dig(resp.hostname);
                    }
                })
                .catch(err => {
                  console.log(err);
                })
                .finally(() =>{
                  this.$data.chargement = false
                });

            // Domain
            } else {
              this.subdomains(info)
              this.whois(info, 'Domain', '#416ea5')
              this.dig(info)
              fetch(`${stc.afr.api}/host/domain/${info}`)
                .then(resp => {
                  if(!resp.ok){ throw 'http.code != 200' }
                  return resp.json()        
                })
                .then(resp => {
                    this.$data.infos.unshift({title: 'Info', text:`Domain Tested: ${info} & Domain Reverse: ${resp.ipv4}`, color:'#a54141', size:'6'})
                    this.nmap(resp.ipv4)
                    if(resp.ipv4 != null){
                      this.whois(resp.ipv4, 'IP', '#41a57b')
                    }
                })
                .catch(err => {
                  console.log(err);
                })
                .finally(() =>{
                  this.$data.chargement = false
                });
            }
        },
        nmap(value){
          this.$data.infos.unshift({title: 'Nmap (~5s)', loading: true, color:'#8341a5', size:'6', id: 'nmap'})
          fetch(`${stc.afr.api}/nmap/${value}`)
          .then(resp => {
              if(!resp.ok){ throw 'http.code != 200' }
              return resp.json()
          })
          .then(resp => {
              var texteToPrint = resp.ports.length > 0 ? `Port Opened: ${resp.ports.join(", ")}` : `Port Opened: No`
              var index = this.$data.infos.findIndex((element) => element.id == 'nmap')
              this.$data.infos[index].text = texteToPrint
              this.$data.infos[index].loading = false
          })
          .catch(err => {
                console.log(err);
          })
        },
        subdomains(value){
          this.$data.infos.push({title: 'Subdomain Explorer (~15s)', loading: true, color:'#2f9aa5', size:'3', id: 'subdomain'})
          fetch(`${stc.afr.api}/subdomains/${value}`)
          .then(resp => {
              if(!resp.ok){ throw 'http.code != 200' }
              return resp.json()       
          })
          .then(resp => {
              var texteToPrint = resp.size > 0 ? `% ${resp.credit}<br>%<br>% ${resp.domain}<br><br>${resp.list.join('<br>')}` : `No subdomains`
              var index = this.$data.infos.findIndex((element) => element.id == 'subdomain')
              this.$data.infos[index].text = texteToPrint
              this.$data.infos[index].loading = false
          })
          .catch(err => {
                console.log(err);
          })
        },
        whois(value, title, color){
          fetch(`${stc.afr.api}/whois/${value}`)
          .then(resp => {
              if(!resp.ok){ throw 'http.code != 200' }
              return resp.json()       
          })
          .then(resp => {
            this.$data.infos.push({title: `Whois ${title}: ${resp.request}`, text: resp.content.replace(/\n/g, "<br/>"), color: color, size:'3'})
          })
          .catch(err => {
                console.log(err);
          })
        },
        dig(value){
          fetch(`${stc.afr.api}/dig/${value}/${this.$data.dns}`)
          .then(resp => {
              if(!resp.ok){ throw 'http.code != 200' }
              return resp.json()       
          })
          .then(resp => {
            this.$data.infos.push({title: `DNS Records: ${resp.request}`, text: resp.content.replace(/\n/g, "<br/>"), color: '#9fa541', size:'3'})
          })
          .catch(err => {
                console.log(err);
          })
        },
    },
  }
</script>